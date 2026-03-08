<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Client\Events\ClientCreatedViaPortal;
use App\Modules\Client\Events\ClientRegistered;
use App\Modules\Document\Events\DocumentUploaded;
use App\Modules\Payment\Events\PaymentReceived;
use App\Modules\Payment\Events\SubscriptionChanged;
use App\Modules\Payment\Events\SubscriptionExpired;
use App\Modules\Scoring\Events\ScoringCalculated;
use Illuminate\Support\Facades\Log;

class LogBusinessEvent
{
    public function handleClientRegistered(ClientRegistered $event): void
    {
        Log::channel('single')->info('Domain Event: ClientRegistered', [
            'client_id' => $event->client->id,
            'agency_id' => $event->agencyId,
        ]);
    }

    public function handlePaymentReceived(PaymentReceived $event): void
    {
        $data = [
            'transaction_id' => $event->transaction->id,
            'agency_id'      => $event->agencyId,
            'amount'         => $event->transaction->amount,
            'currency'       => $event->transaction->currency,
        ];
        Log::channel('single')->info('Domain Event: PaymentReceived', $data);
        Log::channel('billing')->info('Payment received', $data);
    }

    public function handleScoringCalculated(ScoringCalculated $event): void
    {
        Log::channel('single')->info('Domain Event: ScoringCalculated', [
            'client_id'    => $event->clientId,
            'country_code' => $event->countryCode,
            'score'        => $event->score,
            'risk_level'   => $event->riskLevel,
        ]);
    }

    public function handleDocumentUploaded(DocumentUploaded $event): void
    {
        Log::channel('single')->info('Domain Event: DocumentUploaded', [
            'document_id' => $event->document->id,
            'case_id'     => $event->document->case_id ?? null,
            'uploaded_by' => $event->uploadedBy,
            'source'      => $event->source,
            'file_type'   => $event->document->type ?? null,
        ]);
    }

    public function handleSubscriptionChanged(SubscriptionChanged $event): void
    {
        $data = [
            'subscription_id' => $event->subscription->id,
            'agency_id'       => $event->subscription->agency_id,
            'change_type'     => $event->changeType,
            'old_plan'        => $event->oldPlanSlug,
            'new_plan'        => $event->newPlanSlug,
        ];
        Log::channel('single')->info('Domain Event: SubscriptionChanged', $data);
        Log::channel('billing')->info('Subscription changed', $data);
    }

    public function handleSubscriptionExpired(SubscriptionExpired $event): void
    {
        $data = [
            'subscription_id' => $event->subscription->id,
            'agency_id'       => $event->agencyId,
        ];
        Log::channel('single')->info('Domain Event: SubscriptionExpired', $data);
        Log::channel('billing')->warning('Subscription expired', $data);
    }

    public function handleClientCreatedViaPortal(ClientCreatedViaPortal $event): void
    {
        Log::channel('single')->info('Domain Event: ClientCreatedViaPortal', [
            'client_id' => $event->client->id,
            'agency_id' => $event->agencyId,
            'lead_id'   => $event->leadId,
        ]);
    }
}
