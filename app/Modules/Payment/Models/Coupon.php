<?php

namespace App\Modules\Payment\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasUuid;

class Coupon extends BaseModel
{
    use HasUuid;

    protected $fillable = [
        'code', 'description', 'discount_type', 'discount_value',
        'max_uses', 'used_count', 'plan_slug', 'valid_from', 'valid_until', 'is_active',
    ];

    protected $casts = [
        'discount_value' => 'integer',
        'max_uses'       => 'integer',
        'used_count'     => 'integer',
        'valid_from'     => 'date',
        'valid_until'    => 'date',
        'is_active'      => 'boolean',
    ];

    public function isValid(?string $planSlug = null): bool
    {
        if (! $this->is_active) return false;
        if ($this->max_uses > 0 && $this->used_count >= $this->max_uses) return false;
        if ($this->valid_from && now()->lt($this->valid_from)) return false;
        if ($this->valid_until && now()->gt($this->valid_until)) return false;
        if ($this->plan_slug && $planSlug && $this->plan_slug !== $planSlug) return false;

        return true;
    }

    public function calculateDiscount(int $amount): int
    {
        if ($this->discount_type === 'percentage') {
            return (int) round($amount * $this->discount_value / 100);
        }

        return min($this->discount_value, $amount);
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}
