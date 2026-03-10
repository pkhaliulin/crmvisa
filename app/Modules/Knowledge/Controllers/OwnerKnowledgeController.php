<?php

namespace App\Modules\Knowledge\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Knowledge\Models\AgencyKnowledgeNote;
use App\Modules\Knowledge\Models\KnowledgeArticle;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerKnowledgeController extends Controller
{
    // ── Глобальные статьи БЗ ────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = KnowledgeArticle::query()
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }
        if ($request->filled('country_code')) {
            $query->byCountry($request->country_code);
        }
        if ($request->filled('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', $search)
                  ->orWhere('content', 'ilike', $search)
                  ->orWhere('title_uz', 'ilike', $search);
            });
        }

        return ApiResponse::paginated($query->paginate($request->integer('per_page', 20)));
    }

    public function show(string $id): JsonResponse
    {
        $article = KnowledgeArticle::findOrFail($id);

        return ApiResponse::success($article);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_code' => ['nullable', 'string', 'size:2'],
            'visa_type'    => ['nullable', 'string', 'max:50'],
            'category'     => ['required', 'string', 'in:' . implode(',', KnowledgeArticle::categories())],
            'title'        => ['required', 'string', 'max:500'],
            'title_uz'     => ['nullable', 'string', 'max:500'],
            'content'      => ['required', 'string'],
            'content_uz'   => ['nullable', 'string'],
            'summary'      => ['nullable', 'string', 'max:1000'],
            'summary_uz'   => ['nullable', 'string', 'max:1000'],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string', 'max:50'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order'   => ['sometimes', 'integer'],
        ]);

        if (! empty($data['country_code'])) {
            $data['country_code'] = strtoupper($data['country_code']);
        }

        $article = KnowledgeArticle::create($data);

        if ($article->is_published && ! $article->published_at) {
            $article->update(['published_at' => now()]);
        }

        return ApiResponse::created($article);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $article = KnowledgeArticle::findOrFail($id);

        $data = $request->validate([
            'country_code' => ['nullable', 'string', 'size:2'],
            'visa_type'    => ['nullable', 'string', 'max:50'],
            'category'     => ['sometimes', 'string', 'in:' . implode(',', KnowledgeArticle::categories())],
            'title'        => ['sometimes', 'string', 'max:500'],
            'title_uz'     => ['nullable', 'string', 'max:500'],
            'content'      => ['sometimes', 'string'],
            'content_uz'   => ['nullable', 'string'],
            'summary'      => ['nullable', 'string', 'max:1000'],
            'summary_uz'   => ['nullable', 'string', 'max:1000'],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string', 'max:50'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order'   => ['sometimes', 'integer'],
        ]);

        if (! empty($data['country_code'])) {
            $data['country_code'] = strtoupper($data['country_code']);
        }

        $article->update($data);

        if ($article->is_published && ! $article->published_at) {
            $article->update(['published_at' => now()]);
        }

        return ApiResponse::success($article->fresh());
    }

    public function destroy(string $id): JsonResponse
    {
        KnowledgeArticle::findOrFail($id)->delete();

        return ApiResponse::success(null, 'Статья удалена.');
    }

    public function publish(string $id): JsonResponse
    {
        $article = KnowledgeArticle::findOrFail($id);

        if ($article->is_published) {
            $article->unpublish();
        } else {
            $article->publish();
        }

        return ApiResponse::success($article->fresh());
    }

    // ── Статистика ──────────────────────────────────────

    public function stats(): JsonResponse
    {
        return ApiResponse::success([
            'total_articles'     => KnowledgeArticle::count(),
            'published_articles' => KnowledgeArticle::published()->count(),
            'draft_articles'     => KnowledgeArticle::where('is_published', false)->count(),
            'by_category'        => KnowledgeArticle::selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
            'countries_covered'  => KnowledgeArticle::whereNotNull('country_code')
                ->distinct('country_code')
                ->count('country_code'),
            'total_views'        => KnowledgeArticle::sum('view_count'),
            'pending_contributions' => AgencyKnowledgeNote::withoutTenant()
                ->pendingModeration()
                ->count(),
        ]);
    }

    // ── Модерация заметок агентств ──────────────────────

    public function pendingNotes(Request $request): JsonResponse
    {
        $notes = AgencyKnowledgeNote::withoutTenant()
            ->pendingModeration()
            ->with('agency:id,name', 'author:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return ApiResponse::paginated($notes);
    }

    public function moderateNote(Request $request, string $id): JsonResponse
    {
        $note = AgencyKnowledgeNote::withoutTenant()->findOrFail($id);

        $data = $request->validate([
            'action' => ['required', 'in:approve,reject,merge'],
        ]);

        match ($data['action']) {
            'approve' => $note->update(['moderation_status' => 'approved']),
            'reject'  => $note->update(['moderation_status' => 'rejected']),
            'merge'   => $this->mergeNoteToArticle($note),
        };

        return ApiResponse::success($note->fresh());
    }

    private function mergeNoteToArticle(AgencyKnowledgeNote $note): void
    {
        $article = KnowledgeArticle::create([
            'country_code' => $note->country_code,
            'visa_type'    => $note->visa_type,
            'category'     => $note->category ?? 'tips',
            'title'        => $note->title,
            'content'      => $note->content,
            'source'       => 'agency_contribution',
            'is_published' => false,
        ]);

        $note->update([
            'moderation_status'    => 'merged',
            'merged_to_article_id' => $article->id,
        ]);
    }
}
