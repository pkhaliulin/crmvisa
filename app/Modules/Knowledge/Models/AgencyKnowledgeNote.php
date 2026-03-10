<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Agency\Models\Agency;
use App\Modules\User\Models\User;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyKnowledgeNote extends BaseModel
{
    use HasTenant;

    protected $table = 'agency_knowledge_notes';

    protected $fillable = [
        'agency_id',
        'country_code',
        'visa_type',
        'category',
        'title',
        'content',
        'created_by',
        'is_pinned',
        'is_shared',
        'moderation_status',
        'merged_to_article_id',
        'ai_review_score',
        'ai_review_comment',
    ];

    protected $casts = [
        'is_pinned'       => 'boolean',
        'is_shared'       => 'boolean',
        'ai_review_score' => 'float',
    ];

    // ── Relations ───────────────────────────────────────

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mergedArticle(): BelongsTo
    {
        return $this->belongsTo(KnowledgeArticle::class, 'merged_to_article_id');
    }

    // ── Scopes ──────────────────────────────────────────

    public function scopeByCountry(Builder $query, string $code): Builder
    {
        return $query->where('country_code', strtoupper($code));
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeShared(Builder $query): Builder
    {
        return $query->where('is_shared', true);
    }

    public function scopePendingModeration(Builder $query): Builder
    {
        return $query->where('is_shared', true)
            ->where('moderation_status', 'pending');
    }

    // ── Categories ──────────────────────────────────────

    public static function categories(): array
    {
        return [
            'process',
            'tips',
            'contacts',
            'prices',
            'timing',
            'other',
        ];
    }
}
