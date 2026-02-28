<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Services\DocumentService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(protected DocumentService $service) {}

    public function index(string $caseId): JsonResponse
    {
        return ApiResponse::success($this->service->listForCase($caseId));
    }

    public function store(Request $request, string $caseId): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $data = $request->validate([
            'file'      => ['required', 'file', 'max:20480'], // 20 MB
            'type'      => ['required', 'string', 'max:60'],
            'client_id' => ['required', 'uuid', "exists:clients,id,agency_id,{$agencyId}"],
            'notes'     => ['nullable', 'string'],
        ]);

        $document = $this->service->upload(
            $request->file('file'),
            array_merge($data, ['case_id' => $caseId])
        );

        return ApiResponse::created($document);
    }

    public function updateStatus(Request $request, string $caseId, string $documentId): JsonResponse
    {
        $document = Document::where('id', $documentId)
            ->where('case_id', $caseId)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();

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

        $this->service->delete($document);

        return ApiResponse::success(null, 'Document deleted.');
    }
}
