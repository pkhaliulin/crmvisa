<?php

namespace App\Modules\Document\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseChecklist extends BaseModel
{
    use HasTenant;

    protected $table = 'case_checklist';

    protected $fillable = [
        'agency_id',
        'case_id',
        'family_member_id',
        'requirement_id',
        'country_requirement_id',
        'type',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'is_required',
        'responsibility',
        'requirement_level',
        'metadata',
        'document_id',
        'is_checked',
        'status',
        'notes',
        'sort_order',
        'review_status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
        'translation_pages',
        'translation_price',
        'translation_document_id',
        'translated_by',
        'translated_at',
        'ai_analysis',
        'ai_analyzed_at',
        'ai_confidence',
    ];

    protected $casts = [
        'is_required'        => 'boolean',
        'is_checked'         => 'boolean',
        'sort_order'         => 'integer',
        'metadata'           => 'array',
        'reviewed_at'        => 'datetime',
        'translated_at'      => 'datetime',
        'translation_pages'  => 'integer',
        'translation_price'  => 'integer',
        'ai_analysis'        => 'array',
        'ai_analyzed_at'     => 'datetime',
        'ai_confidence'      => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class);
    }

    public function countryRequirement(): BelongsTo
    {
        return $this->belongsTo(CountryVisaRequirement::class, 'country_requirement_id');
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\PublicPortal\Models\PublicUserFamilyMember::class, 'family_member_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeForCase(Builder $query, string $caseId): Builder
    {
        return $query->where('case_id', $caseId)->orderBy('sort_order');
    }

    // -------------------------------------------------------------------------
    // Business logic
    // -------------------------------------------------------------------------

    public function isUploaded(): bool
    {
        return in_array($this->status, ['uploaded', 'approved']);
    }

    public function markUploaded(string $documentId): void
    {
        $this->update([
            'document_id' => $documentId,
            'status'      => 'uploaded',
        ]);
    }
}
