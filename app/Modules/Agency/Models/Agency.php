<?php

namespace App\Modules\Agency\Models;

use App\Modules\Agency\Enums\Plan;
use App\Modules\Payment\Models\AgencyProfile;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Service\Models\AgencyServicePackage;
use App\Support\Abstracts\BaseModel;
use Database\Factories\Modules\Agency\AgencyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Agency extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): AgencyFactory
    {
        return AgencyFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'country',
        'timezone',
        'logo_path',
        'logo_url',
        'plan',
        'plan_expires_at',
        'is_active',
        'is_verified',
        'commission_rate',
        'blocked_at',
        'block_reason',
        'settings',
        'telegram_bot_token',
        'telegram_bot_username',
        'managers_see_all_cases',
        'lead_assignment_mode',
        'api_key',
        'api_key_generated_at',
        'description',
        'description_uz',
        'experience_years',
        'website_url',
        'address',
        'city',
        'rating',
        'reviews_count',
        'top_criterion',
        'primary_color',
        'secondary_color',
        'custom_domain',
        'favicon_url',
        // Юридические реквизиты
        'legal_name',
        'legal_form',
        'inn',
        'legal_address',
        'bank_account',
        'bank_name',
        'bank_mfo',
        'director_name',
        'director_basis',
        'stamp_url',
        'default_refund_policy',
        'default_payment_terms',
    ];

    protected $casts = [
        'plan'                   => Plan::class,
        'plan_expires_at'        => 'datetime',
        'blocked_at'             => 'datetime',
        'is_active'              => 'boolean',
        'is_verified'            => 'boolean',
        'commission_rate'        => 'float',
        'settings'               => 'array',
        'api_key_generated_at'   => 'datetime',
        'managers_see_all_cases' => 'boolean',
        'experience_years'       => 'integer',
        'rating'                 => 'float',
        'reviews_count'          => 'integer',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
        'deleted_at'             => 'datetime',
        'default_refund_policy'  => 'array',
        'default_payment_terms'  => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function users(): HasMany
    {
        return $this->hasMany(\App\Modules\User\Models\User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgencySubscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(AgencySubscription::class)
                    ->whereIn('status', ['active', 'trialing'])
                    ->latestOfMany('starts_at');
    }

    public function marketplaceProfile(): HasOne
    {
        return $this->hasOne(AgencyProfile::class);
    }

    public function ownerRelation(): HasOne
    {
        return $this->hasOne(\App\Modules\User\Models\User::class)->where('role', 'owner');
    }

    public function owner(): ?\App\Modules\User\Models\User
    {
        return $this->ownerRelation()->first();
    }

    public function workCountries(): HasMany
    {
        return $this->hasMany(AgencyWorkCountry::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(AgencyServicePackage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AgencyReview::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // Business logic
    // -------------------------------------------------------------------------

    public function isOnTrial(): bool
    {
        return $this->plan === Plan::Trial;
    }

    public function isPlanActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->plan_expires_at === null) {
            return true;
        }

        return $this->plan_expires_at->isFuture();
    }

    /**
     * Реальный план агентства: из активной подписки или fallback на agency.plan.
     */
    public function effectivePlan(): string
    {
        $sub = AgencySubscription::where('agency_id', $this->id)
            ->active()
            ->latest('starts_at')
            ->first();

        if ($sub && $sub->plan_slug) {
            return $sub->plan_slug;
        }

        return $this->plan instanceof Plan ? $this->plan->value : (string) $this->plan;
    }

    public function planExpiresInDays(): ?int
    {
        if ($this->plan_expires_at === null) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->plan_expires_at, false);
    }
}
