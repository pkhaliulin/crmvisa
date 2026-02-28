<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyProfile extends BaseModel
{
    use \App\Support\Traits\HasUuid;

    protected $table = 'agency_profiles';

    protected $fillable = [
        'agency_id',
        'description',
        'address',
        'website',
        'countries',
        'visa_types',
        'services',
        'is_verified',
        'is_featured',
        'is_visible',
        'rating_avg',
        'reviews_count',
        'completed_cases',
    ];

    protected $casts = [
        'countries'       => 'array',
        'visa_types'      => 'array',
        'services'        => 'array',
        'is_verified'     => 'boolean',
        'is_featured'     => 'boolean',
        'is_visible'      => 'boolean',
        'rating_avg'      => 'integer',
        'reviews_count'   => 'integer',
        'completed_cases' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->where('is_verified', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function ratingDisplay(): string
    {
        return number_format($this->rating_avg / 100, 1);
    }
}
