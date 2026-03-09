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
     * All supported event types with defaults.
     */
    public static function eventTypes(): array
    {
        return [
            'lead.incoming'        => ['channels' => ['database', 'telegram'], 'recipients' => ['owner', 'assigned_manager']],
            'case.created'         => ['channels' => ['database'], 'recipients' => ['owner']],
            'case.status_changed'  => ['channels' => ['database', 'email'], 'recipients' => ['assigned_manager']],
            'case.assigned'        => ['channels' => ['database', 'telegram'], 'recipients' => ['assigned_manager']],
            'sla.violation'        => ['channels' => ['database', 'telegram', 'email'], 'recipients' => ['owner', 'assigned_manager']],
            'sla.warning'          => ['channels' => ['database'], 'recipients' => ['assigned_manager']],
            'document.uploaded'    => ['channels' => ['database'], 'recipients' => ['assigned_manager']],
            'payment.received'     => ['channels' => ['database'], 'recipients' => ['owner']],
            'subscription.changed' => ['channels' => ['database', 'email'], 'recipients' => ['owner']],
            'subscription.expired' => ['channels' => ['database', 'email', 'telegram'], 'recipients' => ['owner']],
            'client.registered'    => ['channels' => ['database'], 'recipients' => ['owner']],
        ];
    }

    /**
     * Get settings for agency+event, falling back to defaults.
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
        return array_merge($defaults, ['enabled' => true]);
    }
}
