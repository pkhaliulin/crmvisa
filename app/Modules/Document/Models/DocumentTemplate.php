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
        'is_repeatable',
        'metadata_schema',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_repeatable'   => 'boolean',
        'is_active'       => 'boolean',
        'sort_order'      => 'integer',
        'metadata_schema' => 'array',
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
