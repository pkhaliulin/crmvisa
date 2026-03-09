<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Client\Events\ClientCreatedViaPortal;
use App\Modules\Client\Events\ClientRegistered;
use App\Modules\Document\Events\DocumentUploaded;
use App\Modules\Notification\Notifications\BusinessNotification;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Payment\Events\PaymentReceived;
use App\Modules\Payment\Events\SubscriptionChanged;
use App\Modules\Payment\Events\SubscriptionExpired;
use App\Modules\Scoring\Events\ScoringCalculated;
use Illuminate\Support\Facades\Log;

class LogBusinessEvent
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handleClientRegistered(ClientRegistered $event): void
    {
        Log::channel('single')->info('Domain Event: ClientRegistered', [
            'client_id' => $event->client->id,
            'agency_id' => $event->agencyId,
        ]);

        $this->notificationService->dispatch(
            $event->agencyId,
            'client.registered',
            new BusinessNotification('client.registered', [
                'client_id'   => $event->client->id,
                'client_name' => $event->client->name,
                'message'     => "Новый клиент: {$event->client->name}",
                'sms'         => "Новый клиент: {$event->client->name}",
            ]),
        );
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

        $amount = number_format($event->transaction->amount, 0, '.', ' ');

        $this->notificationService->dispatch(
            $event->agencyId,
            'payment.received',
            new BusinessNotification('payment.received', [
                'transaction_id' => $event->transaction->id,
                'amount'         => $event->transaction->amount,
                'currency'       => $event->transaction->currency,
                'message'        => "Платёж получен: {$amount} {$event->transaction->currency}",
                'sms'            => "Платёж: {$amount} {$event->transaction->currency}",
            ]),
        );
    }

    public function handleScoringCalculated(ScoringCalculated $event): void
    {
        Log::channel('single')->info('Domain Event: ScoringCalculated', [
            'client_id'    => $event->clientId,
            'country_code' => $event->countryCode,
            'score'        => $event->score,
            'risk_level'   => $event->riskLevel,
        ]);
        // Скоринг не отправляет уведомлений — только логирование
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

        $agencyId = $event->document->case?->agency_id ?? null;
        if (!$agencyId) {
            return;
        }

        $clientName = $event->document->case?->client?->name ?? 'Клиент';

        $this->notificationService->dispatch(
            $agencyId,
            'document.uploaded',
            new BusinessNotification('document.uploaded', [
                'document_id' => $event->document->id,
                'case_id'     => $event->document->case_id,
                'uploaded_by' => $event->uploadedBy,
                'source'      => $event->source,
                'message'     => "Документ загружен: {$clientName} ({$event->source})",
                'sms'         => "Документ загружен клиентом {$clientName}",
            ]),
            ['case' => $event->document->case],
        );
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

        $typeLabel = match ($event->changeType) {
            'upgrade'   => 'повышена',
            'downgrade' => 'понижена',
            'activated' => 'активирована',
            default     => 'изменена',
        };

        $this->notificationService->dispatch(
            $event->subscription->agency_id,
            'subscription.changed',
            new BusinessNotification('subscription.changed', [
                'subscription_id' => $event->subscription->id,
                'change_type'     => $event->changeType,
                'old_plan'        => $event->oldPlanSlug,
                'new_plan'        => $event->newPlanSlug,
                'subject'         => "Подписка {$typeLabel}: {$event->newPlanSlug}",
                'message'         => "Подписка {$typeLabel}: {$event->oldPlanSlug} -> {$event->newPlanSlug}",
                'sms'             => "Подписка {$typeLabel}: {$event->newPlanSlug}",
            ]),
        );
    }

    public function handleSubscriptionExpired(SubscriptionExpired $event): void
    {
        $data = [
            'subscription_id' => $event->subscription->id,
            'agency_id'       => $event->agencyId,
        ];
        Log::channel('single')->info('Domain Event: SubscriptionExpired', $data);
        Log::channel('billing')->warning('Subscription expired', $data);

        $this->notificationService->dispatch(
            $event->agencyId,
            'subscription.expired',
            new BusinessNotification('subscription.expired', [
                'subscription_id' => $event->subscription->id,
                'subject'         => 'Подписка истекла',
                'message'         => 'Ваша подписка истекла. Продлите для продолжения работы.',
                'sms'             => 'Подписка VisaCRM истекла. Продлите подписку.',
            ]),
        );
    }

    public function handleClientCreatedViaPortal(ClientCreatedViaPortal $event): void
    {
        Log::channel('single')->info('Domain Event: ClientCreatedViaPortal', [
            'client_id' => $event->client->id,
            'agency_id' => $event->agencyId,
            'lead_id'   => $event->leadId,
        ]);

        $this->notificationService->dispatch(
            $event->agencyId,
            'client.registered',
            new BusinessNotification('client.registered', [
                'client_id'   => $event->client->id,
                'client_name' => $event->client->name,
                'source'      => 'portal',
                'lead_id'     => $event->leadId,
                'message'     => "Новый клиент через портал: {$event->client->name}",
                'sms'         => "Новый клиент: {$event->client->name} (портал)",
            ]),
        );
    }
}
