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
        'price_monthly',
        'price_yearly',
        'price_uzs',
        'max_managers',
        'max_cases',
        'has_marketplace',
        'has_priority_support',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly'        => 'integer',
        'price_yearly'         => 'integer',
        'price_uzs'            => 'integer',
        'max_managers'         => 'integer',
        'max_cases'            => 'integer',
        'has_marketplace'      => 'boolean',
        'has_priority_support' => 'boolean',
        'is_active'            => 'boolean',
        'sort_order'           => 'integer',
        'features'             => 'array',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
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
