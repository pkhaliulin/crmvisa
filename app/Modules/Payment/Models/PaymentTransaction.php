<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends BaseModel
{
    use \App\Support\Traits\HasUuid;

    protected $fillable = [
        'agency_id',
        'subscription_id',
        'provider',
        'provider_transaction_id',
        'amount',
        'currency',
        'status',
        'description',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'amount'     => 'integer',
        'metadata'   => 'array',
        'paid_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(AgencySubscription::class, 'subscription_id');
    }

    public function scopeSucceeded(Builder $query): Builder
    {
        return $query->where('status', 'succeeded');
    }

    public function amountFormatted(): string
    {
        if ($this->currency === 'UZS') {
            return number_format($this->amount, 0, '.', ' ') . ' сум';
        }

        return '$' . number_format($this->amount / 100, 2);
    }
}
