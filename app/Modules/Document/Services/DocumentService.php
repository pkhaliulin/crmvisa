<?php

namespace App\Modules\Document\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\Document;
use App\Modules\Notification\Notifications\BusinessNotification;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function upload(UploadedFile $file, array $data): Document
    {
        $agencyId = Auth::user()->agency_id;
        $path     = $file->store("agencies/{$agencyId}/cases/{$data['case_id']}", 'documents');

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

        // Уведомление клиенту при отклонении документа (#19)
        if ($status === 'rejected') {
            $this->notifyClientDocumentRejected($document, $notes);
        }

        return $document->fresh();
    }

    /**
     * Уведомить клиента об отклонении документа.
     */
    private function notifyClientDocumentRejected(Document $document, ?string $notes): void
    {
        try {
            $case = VisaCase::with(['client', 'agency'])->find($document->case_id);
            if (!$case?->client) {
                return;
            }

            $message = "Документ «{$document->original_name}» отклонён"
                . ($notes ? ": {$notes}" : '')
                . ". Загрузите исправленную версию.";

            $this->notificationService->dispatchToClient(
                $case->client,
                new BusinessNotification('document.reviewed', [
                    'case_id'       => $case->id,
                    'case_number'   => $case->case_number ?? $case->id,
                    'document_id'   => $document->id,
                    'document_name' => $document->original_name,
                    'review_status' => 'rejected',
                    'notes'         => $notes,
                    'message'       => $message,
                    'sms'           => mb_substr($message, 0, 160),
                ]),
                $case->agency,
                ['database', 'email', 'sms'],
            );
        } catch (\Throwable $e) {
            Log::warning('Document rejection notification failed', [
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    public function delete(Document $document): void
    {
        Storage::disk('documents')->delete($document->file_path);
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
