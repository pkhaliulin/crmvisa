<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Services\DocumentService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DocumentController extends Controller
{
    public function __construct(protected DocumentService $service) {}

    public function index(Request $request, string $caseId): JsonResponse
    {
        $case = VisaCase::where('id', $caseId)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();

        $this->authorize('view', $case);

        return ApiResponse::success($this->service->listForCase($caseId));
    }

    public function store(Request $request, string $caseId): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        // Проверка что заявка принадлежит агентству
        $case = VisaCase::where('id', $caseId)
            ->where('agency_id', $agencyId)
            ->firstOrFail();

        $this->authorize('update', $case);

        $data = $request->validate([
            'file'  => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,tiff,bmp', new \App\Rules\SafeFileName], // 20 MB
            'type'  => ['required', 'string', 'max:60'],
            'notes' => ['nullable', 'string'],
        ]);

        $document = $this->service->upload(
            $request->file('file'),
            array_merge($data, [
                'case_id'   => $caseId,
                'client_id' => $case->client_id,
            ])
        );

        return ApiResponse::created($document);
    }

    public function updateStatus(Request $request, string $caseId, string $documentId): JsonResponse
    {
        $document = Document::where('id', $documentId)
            ->where('case_id', $caseId)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();

        $this->authorize('updateStatus', $document);

        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
            'notes'  => ['nullable', 'string'],
        ]);

        return ApiResponse::success($this->service->updateStatus($document, $data['status'], $data['notes'] ?? null));
    }

    public function destroy(Request $request, string $caseId, string $documentId): JsonResponse
    {
        $document = Document::where('id', $documentId)
            ->where('case_id', $caseId)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();

        $this->authorize('delete', $document);

        $this->service->delete($document);

        return ApiResponse::success(null, 'Document deleted.');
    }

    /**
     * GET /api/v1/cases/{caseId}/documents/zip
     * Скачать все документы заявки одним ZIP-архивом
     */
    public function downloadZip(Request $request, string $caseId): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $case = VisaCase::where('id', $caseId)
                        ->where('agency_id', $request->user()->agency_id)
                        ->firstOrFail();

        $this->authorize('view', $case);

        $documents = Document::where('case_id', $caseId)->get();

        if ($documents->isEmpty()) {
            return ApiResponse::error('No documents to download', null, 404);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'docs_') . '.zip';
        $zip     = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($documents as $doc) {
            $content = Storage::disk('documents')->get($doc->file_path);
            if ($content !== null) {
                $zip->addFromString($doc->original_name, $content);
            }
        }

        $zip->close();

        $zipName  = "documents-{$case->country_code}-{$case->visa_type}-" . substr($caseId, 0, 8) . '.zip';

        return response()->streamDownload(function () use ($tmpFile) {
            readfile($tmpFile);
            @unlink($tmpFile);
        }, $zipName, ['Content-Type' => 'application/zip']);
    }
}
