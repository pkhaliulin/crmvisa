<?php

namespace App\Modules\Document\Models;

use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends BaseModel
{
    use \App\Support\Traits\HasUuid;

    protected $table = 'document_templates';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'type',
        'is_required',
        'default_responsibility',
        'is_repeatable',
        'metadata_schema',
        'is_active',
        'sort_order',
        // AI fields
        'ai_enabled',
        'ai_extraction_schema',
        'ai_validation_rules',
        'ai_stop_factors',
        'ai_success_factors',
        'ai_risk_indicators',
        'manager_instructions',
        'translation_required',
        'max_age_days',
        'confidence_criteria',
    ];

    protected $casts = [
        'is_repeatable'        => 'boolean',
        'is_active'            => 'boolean',
        'sort_order'           => 'integer',
        'metadata_schema'      => 'array',
        // AI fields
        'ai_enabled'           => 'boolean',
        'ai_extraction_schema' => 'array',
        'ai_validation_rules'  => 'array',
        'ai_stop_factors'      => 'array',
        'ai_success_factors'   => 'array',
        'ai_risk_indicators'   => 'array',
        'translation_required' => 'boolean',
        'max_age_days'         => 'integer',
        'confidence_criteria'  => 'array',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function countryRequirements(): HasMany
    {
        return $this->hasMany(CountryVisaRequirement::class, 'document_template_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isUpload(): bool    { return $this->type === 'upload'; }
    public function isCheckbox(): bool  { return $this->type === 'checkbox'; }
}
