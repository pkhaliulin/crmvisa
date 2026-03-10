<?php

namespace App\Modules\Knowledge\Models;

use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class KnowledgeArticle extends BaseModel
{
    protected $table = 'knowledge_articles';

    protected $fillable = [
        'slug',
        'country_code',
        'visa_type',
        'category',
        'title',
        'title_uz',
        'content',
        'content_uz',
        'summary',
        'summary_uz',
        'tags',
        'source',
        'is_published',
        'sort_order',
        'published_at',
        'view_count',
    ];

    protected $casts = [
        'tags'         => 'array',
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
        'view_count'   => 'integer',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title) . '-' . Str::random(6);
            }
        });
    }

    // ── Scopes ──────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByCountry(Builder $query, string $code): Builder
    {
        return $query->where('country_code', strtoupper($code));
    }

    public function scopeByVisaType(Builder $query, string $visaType): Builder
    {
        return $query->where('visa_type', $visaType);
    }

    // ── Helpers ─────────────────────────────────────────

    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    // ── Categories ──────────────────────────────────────

    public static function categories(): array
    {
        return [
            'country_guide',
            'visa_process',
            'documents',
            'requirements',
            'faq',
            'tips',
            'common_mistakes',
            'finance',
            'changes',
        ];
    }
}
