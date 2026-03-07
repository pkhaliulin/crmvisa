<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencySubscription extends BaseModel
{
    // Нет SoftDeletes — история подписок не удаляется
    use \App\Support\Traits\HasUuid;

    protected $fillable = [
        'agency_id',
        'plan_slug',
        'status',
        'billing_period',
        'payment_method',
        'payment_model',
        'earn_first_deducted_total',
        'earn_first_target',
        'activation_fee_paid',
        'activation_paid_at',
        'stripe_subscription_id',
        'stripe_customer_id',
        'starts_at',
        'expires_at',
        'grace_ends_at',
        'cancelled_at',
        'auto_renew',
        'coupon_id',
        'discount_amount',
        'pending_plan_slug',
        'pending_billing_period',
        'pending_change_at',
        'metadata',
    ];

    protected $casts = [
        'starts_at'                  => 'datetime',
        'expires_at'                 => 'datetime',
        'grace_ends_at'              => 'datetime',
        'cancelled_at'               => 'datetime',
        'activation_paid_at'         => 'datetime',
        'activation_fee_paid'        => 'boolean',
        'auto_renew'                 => 'boolean',
        'earn_first_deducted_total'  => 'integer',
        'earn_first_target'          => 'integer',
        'discount_amount'            => 'integer',
        'pending_change_at'          => 'datetime',
        'metadata'                   => 'array',
        'created_at'                 => 'datetime',
        'updated_at'                 => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class, 'plan_slug', 'slug');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'trialing']);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now()->addDays($days))
                     ->where('expires_at', '>', now());
    }

    // -------------------------------------------------------------------------
    // Business logic
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) return false;
        if ($this->expires_at->isFuture()) return false;

        // Grace-period: ещё не заблокирован
        if ($this->grace_ends_at && $this->grace_ends_at->isFuture()) return false;

        return true;
    }

    public function isInGracePeriod(): bool
    {
        return $this->expires_at?->isPast()
            && $this->grace_ends_at?->isFuture();
    }

    public function isEarnFirst(): bool
    {
        return $this->payment_model === 'earn_first';
    }

    public function needsActivationFee(): bool
    {
        return ! $this->activation_fee_paid && $this->plan?->activation_fee_uzs > 0;
    }

    public function hasPendingDowngrade(): bool
    {
        return $this->pending_plan_slug !== null;
    }

    public function cancelPendingDowngrade(): void
    {
        $this->update([
            'pending_plan_slug'     => null,
            'pending_billing_period' => null,
            'pending_change_at'     => null,
        ]);
    }

    public function daysLeft(): ?int
    {
        if ($this->expires_at === null) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->expires_at, false));
    }
}
