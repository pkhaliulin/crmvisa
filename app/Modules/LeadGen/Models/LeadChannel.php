<?php

namespace App\Modules\LeadGen\Models;

use App\Support\Abstracts\BaseModel;

class LeadChannel extends BaseModel
{
    protected $table = 'lead_channels';

    protected $fillable = [
        'code', 'name', 'name_uz', 'category', 'icon',
        'short_description', 'short_description_uz',
        'full_description', 'full_description_uz',
        'how_it_works', 'how_it_works_uz',
        'when_to_use', 'when_not_to_use', 'use_cases',
        'effectiveness', 'effectiveness_factors',
        'complexity', 'launch_speed',
        'requires_budget', 'requires_api',
        'enterprise_only', 'min_plan',
        'required_preparation', 'expected_result',
        'risks', 'best_practices', 'trends',
        'recommended_for', 'cta_actions',
        'sort_order', 'is_active', 'coming_soon',
    ];

    protected $casts = [
        'requires_budget' => 'boolean',
        'enterprise_only' => 'boolean',
        'is_active'       => 'boolean',
        'coming_soon'     => 'boolean',
        'sort_order'      => 'integer',
        'cta_actions'     => 'array',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeCategory($q, string $category)
    {
        return $q->where('category', $category);
    }
}
