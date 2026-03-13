<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\Document;
use App\Support\Helpers\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentDownloadController extends Controller
{
    private const THUMB_WIDTH = 200;
    private const THUMB_HEIGHT = 200;
    private const THUMB_QUALITY = 75;
    private const PREVIEW_QUALITY = 85;

    public function download(Request $request, string $documentId)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired link');
        }

        $document = $this->findDocumentBypassingRls($documentId);

        AuditLog::log('document.downloaded', [
            'document_id' => $document->id,
            'case_id' => $document->case_id,
            'downloaded_by' => $request->user()?->id ?? 'signed_url',
            'ip' => $request->ip(),
        ]);

        $disk = Storage::disk('documents');
        if (!$disk->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return $disk->download($document->file_path, $document->original_name);
    }

    /**
     * Inline preview — отдаёт файл с Content-Disposition: inline.
     * Для изображений — оригинал. Для PDF/Office — конвертирует в JPEG.
     */
    public function preview(Request $request, string $documentId)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired link');
        }

        $document = $this->findDocumentBypassingRls($documentId);
        $disk = Storage::disk('documents');

        if (!$disk->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        $mime = $document->mime_type ?? 'application/octet-stream';

        // Изображения — отдаём как есть inline
        if (str_starts_with($mime, 'image/')) {
            return response($disk->get($document->file_path))
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"')
                ->header('Cache-Control', 'private, max-age=3600');
        }

        // PDF — отдаём inline (браузер умеет показывать)
        if ($mime === 'application/pdf') {
            return response($disk->get($document->file_path))
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"')
                ->header('Cache-Control', 'private, max-age=3600');
        }

        // Office и прочее — конвертируем в JPEG
        $jpeg = $this->convertToJpeg($disk->get($document->file_path), $document->file_path);
        if ($jpeg) {
            return response($jpeg)
                ->header('Content-Type', 'image/jpeg')
                ->header('Content-Disposition', 'inline')
                ->header('Cache-Control', 'private, max-age=3600');
        }

        abort(415, 'Preview not available for this file type');
    }

    /**
     * Миниатюра документа — маленькое JPEG превью (200x200).
     * Кешируется на диске в thumbnails/.
     */
    public function thumbnail(Request $request, string $documentId)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired link');
        }

        $document = $this->findDocumentBypassingRls($documentId);
        $disk = Storage::disk('documents');

        if (!$disk->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        // Проверить кеш на диске
        $thumbPath = 'thumbnails/' . $document->id . '.jpg';
        if ($disk->exists($thumbPath)) {
            return response($disk->get($thumbPath))
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=86400');
        }

        // Генерация миниатюры
        $content = $disk->get($document->file_path);
        $mime = $document->mime_type ?? '';

        $jpeg = null;

        if (str_starts_with($mime, 'image/')) {
            $jpeg = $content;
        } elseif ($mime === 'application/pdf' || str_ends_with($document->file_path, '.pdf')) {
            $jpeg = $this->pdfFirstPageToJpeg($content);
        } else {
            $jpeg = $this->convertToJpeg($content, $document->file_path);
        }

        if (!$jpeg) {
            // Плейсхолдер — серый квадрат с иконкой файла
            return $this->placeholderThumbnail($document->original_name);
        }

        // Ресайз до thumbnail
        $thumb = $this->resizeToThumbnail($jpeg);
        if ($thumb) {
            // Сохранить на диск
            $disk->put($thumbPath, $thumb);
            $jpeg = $thumb;
        }

        return response($jpeg)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function convertToJpeg(string $content, string $filePath): ?string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // PDF
        if ($ext === 'pdf') {
            return $this->pdfFirstPageToJpeg($content);
        }

        // Office
        $officeExts = ['doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'rtf'];
        if (in_array($ext, $officeExts, true)) {
            return $this->officeToJpeg($content, $ext);
        }

        return null;
    }

    private function pdfFirstPageToJpeg(string $pdfContent): ?string
    {
        $tmpPdf = tempnam(sys_get_temp_dir(), 'prev_pdf_');
        $tmpOut = tempnam(sys_get_temp_dir(), 'prev_img_');
        try {
            file_put_contents($tmpPdf, $pdfContent);
            $cmd = sprintf(
                'pdftoppm -jpeg -r 150 -f 1 -l 1 -singlefile %s %s 2>&1',
                escapeshellarg($tmpPdf),
                escapeshellarg($tmpOut)
            );
            exec($cmd, $output, $exitCode);
            $jpegPath = $tmpOut . '.jpg';
            if ($exitCode !== 0 || !file_exists($jpegPath)) {
                return null;
            }
            return file_get_contents($jpegPath);
        } finally {
            @unlink($tmpPdf);
            @unlink($tmpOut);
            @unlink($tmpOut . '.jpg');
        }
    }

    private function officeToJpeg(string $content, string $ext): ?string
    {
        $tmpDir = sys_get_temp_dir() . '/prev_office_' . uniqid();
        @mkdir($tmpDir);
        $tmpFile = $tmpDir . '/doc.' . $ext;
        try {
            file_put_contents($tmpFile, $content);
            $cmd = sprintf(
                'libreoffice --headless --convert-to pdf --outdir %s %s 2>&1',
                escapeshellarg($tmpDir),
                escapeshellarg($tmpFile)
            );
            exec($cmd, $output, $exitCode);
            $pdfPath = $tmpDir . '/doc.pdf';
            if ($exitCode !== 0 || !file_exists($pdfPath)) {
                return null;
            }
            return $this->pdfFirstPageToJpeg(file_get_contents($pdfPath));
        } finally {
            array_map('unlink', glob($tmpDir . '/*'));
            @rmdir($tmpDir);
        }
    }

    private function resizeToThumbnail(string $jpegContent): ?string
    {
        $img = @imagecreatefromstring($jpegContent);
        if (!$img) return null;

        $origW = imagesx($img);
        $origH = imagesy($img);

        // Масштабируем с сохранением пропорций, вписываем в квадрат
        $ratio = min(self::THUMB_WIDTH / $origW, self::THUMB_HEIGHT / $origH);
        $newW = (int) ($origW * $ratio);
        $newH = (int) ($origH * $ratio);

        $thumb = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($thumb, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        ob_start();
        imagejpeg($thumb, null, self::THUMB_QUALITY);
        $result = ob_get_clean();

        imagedestroy($img);
        imagedestroy($thumb);

        return $result;
    }

    /**
     * Найти документ, обходя PostgreSQL RLS.
     * Безопасно: эти эндпоинты защищены signed URL.
     */
    private function findDocumentBypassingRls(string $documentId): Document
    {
        DB::statement("SET app.is_superadmin = 'true'");
        try {
            return Document::withoutGlobalScopes()->findOrFail($documentId);
        } finally {
            DB::statement("RESET app.is_superadmin");
        }
    }

    private function placeholderThumbnail(string $filename): Response
    {
        $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION)) ?: '?';

        $img = imagecreatetruecolor(self::THUMB_WIDTH, self::THUMB_HEIGHT);
        $bg = imagecolorallocate($img, 243, 244, 246);
        $textColor = imagecolorallocate($img, 107, 114, 128);
        imagefill($img, 0, 0, $bg);

        // Текст с расширением файла в центре
        $fontSize = 4;
        $textWidth = imagefontwidth($fontSize) * strlen($ext);
        $textHeight = imagefontheight($fontSize);
        $x = (int) ((self::THUMB_WIDTH - $textWidth) / 2);
        $y = (int) ((self::THUMB_HEIGHT - $textHeight) / 2);
        imagestring($img, $fontSize, $x, $y, $ext, $textColor);

        ob_start();
        imagejpeg($img, null, 80);
        $result = ob_get_clean();
        imagedestroy($img);

        return response($result)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
