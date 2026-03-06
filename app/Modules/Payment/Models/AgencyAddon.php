<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyAddon extends BaseModel
{
    use HasUuid;

    protected $fillable = [
        'agency_id', 'addon_id', 'subscription_id', 'status', 'starts_at', 'expires_at',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(PlanAddon::class, 'addon_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
