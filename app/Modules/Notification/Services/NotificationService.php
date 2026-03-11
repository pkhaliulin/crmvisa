<?php

namespace App\Modules\Notification\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Notification\Models\NotificationSetting;
use App\Modules\User\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Маппинг каналов: ключ настройки → класс/идентификатор Laravel.
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
     * Отправить уведомление сотрудникам агентства (agency audience).
     *
     * @param string       $agencyId
     * @param string       $eventType    e.g. 'lead.incoming', 'case.created'
     * @param Notification $notification Экземпляр Laravel Notification
     * @param array        $context      ['case' => VisaCase, 'assigned_to' => uuid, ...]
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

        $channels = $this->resolveChannels($settings['channels']);
        if (empty($channels)) {
            return;
        }

        // Устанавливаем каналы на notification
        if (method_exists($notification, 'setChannels')) {
            $notification->setChannels($channels);
        }

        // Устанавливаем бренд (agency audience → VisaCRM)
        $agency = Agency::find($agencyId);
        $brand  = BrandResolver::resolve('agency', $agency);

        if (method_exists($notification, 'setBrand')) {
            $notification->setBrand($brand);
        }

        // Получатели
        $recipients = $this->resolveRecipients($agencyId, $settings['recipients'], $context);

        foreach ($recipients as $recipient) {
            try {
                $recipient->notify($notification);
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
     * Отправить уведомление клиенту (client audience).
     * Бренд определяется по source клиента:
     *   marketplace → VisaBor
     *   direct/referral → имя агентства
     *
     * @param mixed        $client       Client или PublicUser (Notifiable)
     * @param Notification $notification
     * @param Agency|null  $agency
     * @param array        $channels     Каналы для отправки
     */
    public function dispatchToClient(
        mixed $client,
        Notification $notification,
        ?Agency $agency = null,
        array $channels = ['database', 'email', 'telegram'],
    ): void {
        $clientSource = $client->source ?? 'direct';
        $brand = BrandResolver::resolve('client', $agency, $clientSource);

        if (method_exists($notification, 'setBrand')) {
            $notification->setBrand($brand);
        }

        $resolvedChannels = $this->resolveChannels($channels);

        // Фильтруем каналы по доступности клиента
        $available = [];
        foreach ($resolvedChannels as $ch) {
            if ($ch === 'database') {
                $available[] = $ch;
            } elseif ($ch === 'mail' && !empty($client->email)) {
                $available[] = $ch;
            } elseif ($ch === \App\Modules\Notification\Channels\TelegramChannel::class && !empty($client->telegram_chat_id)) {
                $available[] = $ch;
            } elseif ($ch === \App\Modules\Notification\Channels\SmsChannel::class && !empty($client->phone)) {
                $available[] = $ch;
            }
        }

        if (empty($available)) {
            return;
        }

        if (method_exists($notification, 'setChannels')) {
            $notification->setChannels($available);
        }

        try {
            $client->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Client notification failed', [
                'client_id' => $client->id ?? null,
                'brand'     => $brand['name'],
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Резолвить каналы: ключи настройки → идентификаторы Laravel.
     */
    private function resolveChannels(array $channelKeys): array
    {
        $channels = [];
        foreach ($channelKeys as $ch) {
            if (isset(self::CHANNEL_MAP[$ch])) {
                $channels[] = self::CHANNEL_MAP[$ch];
            }
        }
        return $channels;
    }

    /**
     * Резолвить получателей: правила → модели User.
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
