<?php

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Models\NotificationSetting;
use App\Modules\User\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Supported channels map.
     * Each key is a channel name, value is the Laravel notification channel class or identifier.
     */
    private const CHANNEL_MAP = [
        'database'  => 'database',
        'email'     => 'mail',
        'telegram'  => \App\Modules\Notification\Channels\TelegramChannel::class,
        'sms'       => \App\Modules\Notification\Channels\SmsChannel::class,
        'push'      => \App\Modules\Notification\Channels\PushChannel::class,
        'call'      => \App\Modules\Notification\Channels\CallChannel::class,
    ];

    /**
     * Dispatch a notification based on agency settings.
     *
     * @param string $agencyId
     * @param string $eventType e.g. 'lead.incoming'
     * @param Notification $notification The Laravel Notification instance
     * @param array $context ['case' => VisaCase, 'assigned_to' => uuid, ...]
     */
    public function dispatch(
        string $agencyId,
        string $eventType,
        Notification $notification,
        array $context = [],
    ): void {
        $settings = NotificationSetting::forAgencyEvent($agencyId, $eventType);

        if (!$settings['enabled'] || empty($settings['channels'])) {
            return;
        }

        // Resolve notification channels
        $channels = [];
        foreach ($settings['channels'] as $ch) {
            if (isset(self::CHANNEL_MAP[$ch])) {
                $channels[] = self::CHANNEL_MAP[$ch];
            }
        }

        if (empty($channels)) {
            return;
        }

        // Set channels on notification
        if (method_exists($notification, 'setChannels')) {
            $notification->setChannels($channels);
        }

        // Resolve recipients
        $recipients = $this->resolveRecipients($agencyId, $settings['recipients'], $context);

        foreach ($recipients as $recipient) {
            try {
                // notifyNow вместо notify — не зависим от queue worker.
                // Когда queue worker будет запущен, можно заменить на notify().
                $recipient->notifyNow($notification);
            } catch (\Throwable $e) {
                Log::warning('Notification dispatch failed', [
                    'event_type' => $eventType,
                    'agency_id'  => $agencyId,
                    'user_id'    => $recipient->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Resolve User models to notify based on recipient rules.
     */
    private function resolveRecipients(string $agencyId, array $recipientRules, array $context): array
    {
        $recipients = collect();

        foreach ($recipientRules as $rule) {
            switch ($rule) {
                case 'owner':
                    $owner = User::where('agency_id', $agencyId)->where('role', 'owner')->first();
                    if ($owner) $recipients->push($owner);
                    break;

                case 'assigned_manager':
                    $assignedTo = $context['assigned_to'] ?? ($context['case']->assigned_to ?? null);
                    if ($assignedTo) {
                        $manager = User::find($assignedTo);
                        if ($manager) $recipients->push($manager);
                    }
                    break;

                case 'all_managers':
                    $managers = User::where('agency_id', $agencyId)
                        ->whereIn('role', ['owner', 'manager'])
                        ->where('is_active', true)
                        ->get();
                    $recipients = $recipients->merge($managers);
                    break;
            }
        }

        return $recipients->unique('id')->values()->all();
    }
}
