<?php

namespace App\Modules\Notification\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;

class NotificationSetting extends BaseModel
{
    use HasTenant;

    protected $table = 'notification_settings';

    protected $fillable = [
        'agency_id',
        'event_type',
        'channels',
        'recipients',
        'is_enabled',
    ];

    protected $casts = [
        'channels'   => 'array',
        'recipients' => 'array',
        'is_enabled' => 'boolean',
    ];

    /**
     * Все типы событий с дефолтными настройками.
     *
     * audience:
     *   'agency'  — уведомления для менеджеров/владельцев (бренд VisaCRM)
     *   'client'  — уведомления клиенту (бренд VisaBor или имя агентства)
     *   'both'    — и клиенту, и менеджерам
     */
    public static function eventTypes(): array
    {
        return [
            // === Лиды ===
            'lead.incoming' => [
                'channels'   => ['database', 'telegram'],
                'recipients' => ['owner', 'assigned_manager'],
                'audience'   => 'agency',
            ],

            // === Заявки (для агентства) ===
            'case.created' => [
                'channels'   => ['database'],
                'recipients' => ['owner'],
                'audience'   => 'agency',
            ],
            'case.status_changed' => [
                'channels'   => ['database', 'email'],
                'recipients' => ['assigned_manager'],
                'audience'   => 'both',
            ],
            'case.assigned' => [
                'channels'   => ['database', 'telegram'],
                'recipients' => ['assigned_manager'],
                'audience'   => 'agency',
            ],
            'case.completed' => [
                'channels'   => ['database', 'email', 'telegram'],
                'recipients' => ['owner', 'assigned_manager'],
                'audience'   => 'both',
            ],
            'case.rejected' => [
                'channels'   => ['database', 'email', 'telegram'],
                'recipients' => ['owner', 'assigned_manager'],
                'audience'   => 'both',
            ],
            'case.cancelled' => [
                'channels'   => ['database'],
                'recipients' => ['owner', 'assigned_manager'],
                'audience'   => 'both',
            ],

            // === SLA ===
            'sla.violation' => [
                'channels'   => ['database', 'telegram', 'email'],
                'recipients' => ['owner', 'assigned_manager'],
                'audience'   => 'agency',
            ],
            'sla.warning' => [
                'channels'   => ['database'],
                'recipients' => ['assigned_manager'],
                'audience'   => 'agency',
            ],

            // === Документы ===
            'document.uploaded' => [
                'channels'   => ['database'],
                'recipients' => ['assigned_manager'],
                'audience'   => 'agency',
            ],
            'document.reviewed' => [
                'channels'   => ['database'],
                'recipients' => ['assigned_manager'],
                'audience'   => 'both',
            ],

            // === Платежи и подписки ===
            'payment.received' => [
                'channels'   => ['database'],
                'recipients' => ['owner'],
                'audience'   => 'agency',
            ],
            'subscription.changed' => [
                'channels'   => ['database', 'email'],
                'recipients' => ['owner'],
                'audience'   => 'agency',
            ],
            'subscription.expired' => [
                'channels'   => ['database', 'email', 'telegram'],
                'recipients' => ['owner'],
                'audience'   => 'agency',
            ],

            // === Клиенты ===
            'client.registered' => [
                'channels'   => ['database'],
                'recipients' => ['owner'],
                'audience'   => 'agency',
            ],
            'client.portal_created' => [
                'channels'   => ['database'],
                'recipients' => ['owner'],
                'audience'   => 'agency',
            ],

            // === Скоринг ===
            'scoring.completed' => [
                'channels'   => ['database'],
                'recipients' => ['assigned_manager'],
                'audience'   => 'both',
            ],
        ];
    }

    /**
     * Получить настройки для agency+event, fallback на defaults.
     */
    public static function forAgencyEvent(string $agencyId, string $eventType): array
    {
        $setting = static::where('agency_id', $agencyId)
            ->where('event_type', $eventType)
            ->first();

        if ($setting && $setting->is_enabled) {
            return [
                'channels'   => $setting->channels,
                'recipients' => $setting->recipients,
                'enabled'    => true,
            ];
        }

        if ($setting && !$setting->is_enabled) {
            return ['channels' => [], 'recipients' => [], 'enabled' => false];
        }

        // Default
        $defaults = static::eventTypes()[$eventType] ?? ['channels' => ['database'], 'recipients' => ['owner']];
        return [
            'channels'   => $defaults['channels'],
            'recipients' => $defaults['recipients'],
            'enabled'    => true,
        ];
    }

    /**
     * Аудитория события: 'agency', 'client', 'both'.
     */
    public static function audienceFor(string $eventType): string
    {
        $types = static::eventTypes();
        return $types[$eventType]['audience'] ?? 'agency';
    }
}
