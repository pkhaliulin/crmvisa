<?php

namespace App\Modules\Agency\Models;

use App\Modules\Agency\Enums\Plan;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Agency extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'country',
        'timezone',
        'logo_path',
        'plan',
        'plan_expires_at',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'plan'            => Plan::class,
        'plan_expires_at' => 'datetime',
        'is_active'       => 'boolean',
        'settings'        => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

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

    public function planExpiresInDays(): ?int
    {
        if ($this->plan_expires_at === null) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->plan_expires_at, false);
    }
}
