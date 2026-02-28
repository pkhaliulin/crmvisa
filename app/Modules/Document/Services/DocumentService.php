<?php

namespace App\Modules\Document\Services;

use App\Modules\Document\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function upload(UploadedFile $file, array $data): Document
    {
        $agencyId = Auth::user()->agency_id;
        $path     = $file->store("agencies/{$agencyId}/cases/{$data['case_id']}", 'public');

        return Document::create([
            'agency_id'     => $agencyId,
            'case_id'       => $data['case_id'],
            'client_id'     => $data['client_id'],
            'uploaded_by'   => Auth::id(),
            'type'          => $data['type'],
            'original_name' => $file->getClientOriginalName(),
            'file_path'     => $path,
            'mime_type'     => $file->getMimeType(),
            'size'          => $file->getSize(),
            'notes'         => $data['notes'] ?? null,
        ]);
    }

    public function updateStatus(Document $document, string $status, ?string $notes = null): Document
    {
        $document->update(array_filter([
            'status' => $status,
            'notes'  => $notes,
        ], fn ($v) => $v !== null));

        return $document->fresh();
    }

    public function delete(Document $document): void
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
    }

    public function listForCase(string $caseId): \Illuminate\Database\Eloquent\Collection
    {
        return Document::where('case_id', $caseId)
            ->with('uploader:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
