<?php

namespace App\Modules\Document\Models;

use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CountryVisaRequirement extends BaseModel
{
    use \App\Support\Traits\HasUuid;

    protected $table = 'country_visa_requirements';

    protected $fillable = [
        'country_code',
        'visa_type',
        'document_template_id',
        'requirement_level',
        'notes',
        'override_metadata',
        'display_order',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'display_order'     => 'integer',
        'override_metadata' => 'array',
        'effective_from'    => 'date',
        'effective_to'      => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
                     ->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()))
                     ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()));
    }

    public function scopeForCountry(Builder $query, string $countryCode, string $visaType): Builder
    {
        return $query->where(function ($q) use ($countryCode, $visaType) {
            // Точное совпадение
            $q->where(fn ($q) => $q->where('country_code', $countryCode)->where('visa_type', $visaType))
              // Для всех типов виз данной страны
              ->orWhere(fn ($q) => $q->where('country_code', $countryCode)->where('visa_type', '*'))
              // Глобальные для всех стран
              ->orWhere(fn ($q) => $q->where('country_code', '*')->where('visa_type', '*'));
        });
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('requirement_level', 'required');
    }

    public function scopeRecommended(Builder $query): Builder
    {
        return $query->where('requirement_level', 'recommended');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Итоговый metadata с учётом override из pivot */
    public function effectiveMetadata(): ?array
    {
        $schema   = $this->template?->metadata_schema ?? [];
        $override = $this->override_metadata ?? [];

        return array_merge($schema, $override) ?: null;
    }

    public function isRequired(): bool          { return $this->requirement_level === 'required'; }
    public function isRecommended(): bool       { return $this->requirement_level === 'recommended'; }
    public function isConfirmationOnly(): bool  { return $this->requirement_level === 'confirmation_only'; }
}
