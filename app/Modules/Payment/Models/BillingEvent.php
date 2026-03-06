<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BillingEvent extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';
    public $timestamps   = false;

    protected $fillable = [
        'id', 'agency_id', 'actor_id', 'event',
        'entity_type', 'entity_id', 'payload', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            if (! $entry->id) {
                $entry->id = Str::uuid()->toString();
            }
            if (! $entry->created_at) {
                $entry->created_at = now();
            }
        });
    }

    public static function log(
        string  $event,
        ?string $agencyId = null,
        ?string $actorId = null,
        ?string $entityType = null,
        ?string $entityId = null,
        array   $payload = [],
    ): self {
        return self::create([
            'event'       => $event,
            'agency_id'   => $agencyId,
            'actor_id'    => $actorId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'payload'     => $payload,
            'ip_address'  => request()?->ip(),
        ]);
    }
}
