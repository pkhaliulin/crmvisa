<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends BaseModel
{
    use \App\Support\Traits\HasUuid;

    protected $fillable = [
        'agency_id',
        'subscription_id',
        'provider',
        'external_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'paid_at',
        'error_message',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'metadata'   => 'array',
        'paid_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AgencySubscription::class, 'subscription_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForAgency(Builder $query, string $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markCompleted(string $externalId): void
    {
        $this->update([
            'status'      => 'completed',
            'external_id' => $externalId,
            'paid_at'     => now(),
        ]);
    }

    public function markFailed(string $errorMessage, ?string $externalId = null): void
    {
        $this->update([
            'status'        => 'failed',
            'external_id'   => $externalId ?? $this->external_id,
            'error_message' => $errorMessage,
        ]);
    }

    public function amountFormatted(): string
    {
        return number_format((float) $this->amount, 0, '.', ' ') . ' ' . $this->currency;
    }
}
