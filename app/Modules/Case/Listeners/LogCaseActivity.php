<?php

namespace App\Modules\Case\Listeners;

use App\Modules\Case\Models\CaseActivity;
use App\Modules\Document\Events\DocumentUploaded;
use App\Modules\Payment\Events\PaymentReceived;
use Illuminate\Support\Facades\Log;

class LogCaseActivity
{
    public function handleDocumentUploaded(DocumentUploaded $event): void
    {
        $doc = $event->document;
        $caseId = $doc->case_id;
        if (! $caseId) {
            return;
        }

        try {
            CaseActivity::create([
                'case_id'     => $caseId,
                'user_id'     => $event->uploadedBy,
                'type'        => 'document_upload',
                'description' => "Документ загружен: {$doc->name}",
                'metadata'    => [
                    'document_id'   => $doc->id,
                    'document_name' => $doc->name,
                    'source'        => $event->source,
                ],
                'is_internal' => false,
            ]);
        } catch (\Throwable $e) {
            Log::debug('Failed to log case activity for DocumentUploaded', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handlePaymentReceived(PaymentReceived $event): void
    {
        $tx = $event->transaction;

        // Find case_id from transaction metadata or related models
        $caseId = $tx->metadata['case_id'] ?? null;
        if (! $caseId) {
            return;
        }

        try {
            CaseActivity::create([
                'case_id'     => $caseId,
                'user_id'     => null,
                'type'        => 'payment',
                'description' => 'Оплата получена',
                'metadata'    => [
                    'transaction_id' => $tx->id,
                    'amount'         => $tx->amount ?? null,
                    'currency'       => $tx->currency ?? null,
                ],
                'is_internal' => false,
            ]);
        } catch (\Throwable $e) {
            Log::debug('Failed to log case activity for PaymentReceived', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
