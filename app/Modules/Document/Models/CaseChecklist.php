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
        'requirement_id',
        'country_requirement_id',
        'type',
        'name',
        'description',
        'is_required',
        'requirement_level',
        'metadata',
        'document_id',
        'is_checked',
        'status',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_checked'  => 'boolean',
        'sort_order'  => 'integer',
        'metadata'    => 'array',
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
