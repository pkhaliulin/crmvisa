<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlan extends Model
{
    protected $primaryKey = 'slug';
    protected $keyType    = 'string';
    public $incrementing  = false;

    protected $fillable = [
        'slug',
        'name',
        'name_uz',
        'description',
        'price_monthly',
        'price_yearly',
        'price_uzs',
        'activation_fee_uzs',
        'earn_first_enabled',
        'earn_first_deduction_pct',
        'max_managers',
        'max_cases',
        'max_leads_per_month',
        'max_concurrent_sessions',
        'has_marketplace',
        'has_priority_support',
        'has_api_access',
        'has_custom_domain',
        'has_white_label',
        'has_analytics',
        'trial_days',
        'grace_period_days',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'features',
        'is_active',
        'is_public',
        'is_recommended',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly'            => 'integer',
        'price_yearly'             => 'integer',
        'price_uzs'                => 'integer',
        'activation_fee_uzs'       => 'integer',
        'earn_first_enabled'       => 'boolean',
        'earn_first_deduction_pct' => 'integer',
        'max_managers'             => 'integer',
        'max_cases'                => 'integer',
        'max_leads_per_month'      => 'integer',
        'max_concurrent_sessions'  => 'integer',
        'has_marketplace'          => 'boolean',
        'has_priority_support'     => 'boolean',
        'has_api_access'           => 'boolean',
        'has_custom_domain'        => 'boolean',
        'has_white_label'          => 'boolean',
        'has_analytics'            => 'boolean',
        'trial_days'               => 'integer',
        'grace_period_days'        => 'integer',
        'is_active'                => 'boolean',
        'is_public'                => 'boolean',
        'is_recommended'           => 'boolean',
        'sort_order'               => 'integer',
        'features'                 => 'array',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(AgencySubscription::class, 'plan_slug', 'slug');
    }

    public function isUnlimitedManagers(): bool
    {
        return $this->max_managers === 0;
    }

    public function isUnlimitedCases(): bool
    {
        return $this->max_cases === 0;
    }

    public function priceMonthlyUsd(): float
    {
        return $this->price_monthly / 100;
    }

    public function priceYearlyUsd(): float
    {
        return $this->price_yearly / 100;
    }
}
