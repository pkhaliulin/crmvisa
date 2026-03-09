<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\Document;
use App\Support\Helpers\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentDownloadController extends Controller
{
    public function download(Request $request, string $documentId)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired link');
        }

        $document = Document::findOrFail($documentId);

        // Audit log
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
}
