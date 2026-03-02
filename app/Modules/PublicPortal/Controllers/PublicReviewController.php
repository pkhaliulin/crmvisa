<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyReview;
use App\Modules\Case\Models\VisaCase;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicReviewController extends Controller
{
    /**
     * GET /public/agencies/{id}/reviews?sort=latest|positive|negative
     */
    public function index(Request $request, string $agencyId): JsonResponse
    {
        $sort = $request->input('sort', 'latest');

        $query = AgencyReview::where('agency_id', $agencyId)
            ->where('is_published', true);

        match ($sort) {
            'positive' => $query->orderByDesc('rating')->orderByDesc('created_at'),
            'negative' => $query->orderBy('rating')->orderByDesc('created_at'),
            default    => $query->orderByDesc('created_at'),
        };

        $reviews = $query->paginate(8);

        // Статистика
        $all      = AgencyReview::where('agency_id', $agencyId)->where('is_published', true);
        $avgRating = $all->avg('rating');
        $total     = $all->count();

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = AgencyReview::where('agency_id', $agencyId)
                ->where('is_published', true)
                ->where('rating', $i)
                ->count();
        }

        return ApiResponse::success([
            'reviews' => collect($reviews->items())->map(fn ($r) => [
                'id'          => $r->id,
                'client_name' => $r->client_name,
                'rating'      => $r->rating,
                'comment'     => $r->comment,
                'created_at'  => $r->created_at?->toDateString(),
            ]),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'total'        => $reviews->total(),
            ],
            'stats' => [
                'avg_rating'   => $avgRating ? round($avgRating, 1) : null,
                'total'        => $total,
                'distribution' => $distribution,
            ],
        ]);
    }

    /**
     * POST /public/agencies/{id}/reviews
     * Оставить отзыв. Требуется хотя бы одна заявка с этим агентством.
     */
    public function store(Request $request, string $agencyId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $agency = Agency::findOrFail($agencyId);

        // Проверяем что есть заявка с агентством
        $hasCase = VisaCase::where('agency_id', $agencyId)
            ->whereHas('client', fn ($q) => $q->where('phone', $publicUser->phone))
            ->exists();

        if (! $hasCase) {
            return ApiResponse::error(
                'Оставить отзыв можно только если у вас есть заявка в этом агентстве.',
                null, 403
            );
        }

        // Один отзыв на агентство
        $existing = AgencyReview::where('public_user_id', $publicUser->id)
            ->where('agency_id', $agencyId)
            ->first();

        if ($existing) {
            return ApiResponse::error('Вы уже оставили отзыв для этого агентства.', null, 409);
        }

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review = AgencyReview::create([
            'agency_id'      => $agencyId,
            'public_user_id' => $publicUser->id,
            'client_name'    => $publicUser->name ?? 'Клиент',
            'rating'         => $data['rating'],
            'comment'        => $data['comment'] ?? null,
            'is_published'   => true,
        ]);

        // Обновляем кешированный рейтинг агентства
        $avg   = AgencyReview::where('agency_id', $agencyId)->where('is_published', true)->avg('rating');
        $count = AgencyReview::where('agency_id', $agencyId)->where('is_published', true)->count();
        $agency->update(['rating' => round((float) $avg, 2), 'reviews_count' => $count]);

        return ApiResponse::created([
            'id'          => $review->id,
            'client_name' => $review->client_name,
            'rating'      => $review->rating,
            'comment'     => $review->comment,
            'created_at'  => $review->created_at?->toDateString(),
        ], 'Отзыв успешно опубликован');
    }

    /**
     * GET /public/me/can-review/{agencyId}
     * Может ли текущий пользователь оставить отзыв.
     */
    public function canReview(Request $request, string $agencyId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $hasCase = VisaCase::where('agency_id', $agencyId)
            ->whereHas('client', fn ($q) => $q->where('phone', $publicUser->phone))
            ->exists();

        $hasReview = AgencyReview::where('public_user_id', $publicUser->id)
            ->where('agency_id', $agencyId)
            ->exists();

        return ApiResponse::success([
            'can_review' => $hasCase && ! $hasReview,
            'has_review' => $hasReview,
            'has_case'   => $hasCase,
        ]);
    }
}
