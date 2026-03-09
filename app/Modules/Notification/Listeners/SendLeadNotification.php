<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\LeadGen\Events\LeadIncoming;
use App\Modules\Notification\Notifications\IncomingLeadNotification;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendLeadNotification
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(LeadIncoming $event): void
    {
        Log::channel('single')->info('Domain Event: LeadIncoming', [
            'case_id'      => $event->case->id,
            'agency_id'    => $event->agencyId,
            'client_id'    => $event->client->id,
            'source'       => $event->source,
            'channel_code' => $event->channelCode,
        ]);

        try {
            activity()
                ->performedOn($event->case)
                ->withProperties([
                    'source'       => $event->source,
                    'channel_code' => $event->channelCode,
                    'client_name'  => $event->client->name,
                ])
                ->log('Новый лид через API');
        } catch (\Throwable $e) {
            Log::channel('single')->debug('Activity log unavailable for LeadIncoming', [
                'error' => $e->getMessage(),
            ]);
        }

        $notification = new IncomingLeadNotification(
            $event->case,
            $event->client,
            $event->source,
        );

        $this->notificationService->dispatch(
            $event->agencyId,
            'lead.incoming',
            $notification,
            ['case' => $event->case],
        );
    }
}
