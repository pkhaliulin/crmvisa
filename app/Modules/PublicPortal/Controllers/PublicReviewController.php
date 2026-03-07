<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyReview;
use App\Modules\Case\Models\VisaCase;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicReviewController extends Controller
{
    /** Названия критериев для API */
    const CRITERIA = ['punctuality', 'quality', 'communication', 'professionalism', 'price_quality'];

    const CRITERIA_LABELS = [
        'punctuality'     => 'пунктуальность',
        'quality'         => 'качество услуг',
        'communication'   => 'коммуникацию',
        'professionalism' => 'профессионализм',
        'price_quality'   => 'соотношение цены и качества',
    ];

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

        // Статистика — один запрос для распределения + общих метрик
        $distRows = AgencyReview::where('agency_id', $agencyId)
            ->where('is_published', true)
            ->selectRaw('rating, COUNT(*) as cnt')
            ->groupBy('rating')
            ->pluck('cnt', 'rating');

        $distribution = [];
        $total = 0;
        $ratingSum = 0;
        for ($i = 1; $i <= 5; $i++) {
            $count = (int) ($distRows[$i] ?? 0);
            $distribution[$i] = $count;
            $total += $count;
            $ratingSum += $i * $count;
        }
        $avgRating = $total > 0 ? round($ratingSum / $total, 1) : null;

        // Средние по критериям — один запрос
        $criteriaAvg = [];
        $criteriaRow = AgencyReview::where('agency_id', $agencyId)
            ->where('is_published', true)
            ->selectRaw(implode(', ', array_map(
                fn ($c) => "AVG($c) as avg_$c",
                self::CRITERIA
            )))
            ->first();

        if ($criteriaRow) {
            foreach (self::CRITERIA as $c) {
                $avg = $criteriaRow->{"avg_$c"};
                if ($avg) $criteriaAvg[$c] = round((float) $avg, 1);
            }
        }

        return ApiResponse::success([
            'reviews' => collect($reviews->items())->map(fn ($r) => [
                'id'              => $r->id,
                'client_name'     => $r->client_name,
                'rating'          => $r->rating,
                'punctuality'     => $r->punctuality,
                'quality'         => $r->quality,
                'communication'   => $r->communication,
                'professionalism' => $r->professionalism,
                'price_quality'   => $r->price_quality,
                'comment'         => $r->comment,
                'created_at'      => $r->created_at?->toDateString(),
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
                'criteria_avg' => $criteriaAvg,
            ],
        ]);
    }

    /**
     * POST /public/agencies/{id}/reviews
     * Принимает 5 критериев + комментарий. Требуется заявка с этим агентством.
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
            'punctuality'     => ['required', 'integer', 'min:1', 'max:5'],
            'quality'         => ['required', 'integer', 'min:1', 'max:5'],
            'communication'   => ['required', 'integer', 'min:1', 'max:5'],
            'professionalism' => ['required', 'integer', 'min:1', 'max:5'],
            'price_quality'   => ['required', 'integer', 'min:1', 'max:5'],
            'comment'         => ['nullable', 'string', 'max:1000'],
            'case_id'         => ['nullable', 'uuid'],
        ]);

        // Итоговый рейтинг — среднее из 5 критериев
        $criteriaValues = [
            $data['punctuality'],
            $data['quality'],
            $data['communication'],
            $data['professionalism'],
            $data['price_quality'],
        ];
        $overallRating = round(array_sum($criteriaValues) / count($criteriaValues), 1);

        $review = AgencyReview::create([
            'agency_id'       => $agencyId,
            'public_user_id'  => $publicUser->id,
            'case_id'         => $data['case_id'] ?? null,
            'client_name'     => $publicUser->name ?? 'Клиент',
            'rating'          => $overallRating,
            'punctuality'     => $data['punctuality'],
            'quality'         => $data['quality'],
            'communication'   => $data['communication'],
            'professionalism' => $data['professionalism'],
            'price_quality'   => $data['price_quality'],
            'comment'         => $data['comment'] ?? null,
            'is_published'    => true,
        ]);

        // Обновляем кешированный рейтинг + лучший критерий
        $this->refreshAgencyStats($agency);

        return ApiResponse::created([
            'id'          => $review->id,
            'client_name' => $review->client_name,
            'rating'      => $review->rating,
            'created_at'  => $review->created_at?->toDateString(),
        ], 'Отзыв успешно опубликован');
    }

    /**
     * GET /public/me/can-review/{agencyId}
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

    /**
     * Пересчитать и сохранить рейтинг + top_criterion для агентства.
     */
    private function refreshAgencyStats(Agency $agency): void
    {
        $reviews = AgencyReview::where('agency_id', $agency->id)
            ->where('is_published', true)
            ->get();

        $count = $reviews->count();
        $avg   = $count > 0 ? round($reviews->avg('rating'), 2) : null;

        // Определить лучший критерий
        $topCriterion = null;
        if ($count > 0) {
            $criteriaAvg = [];
            foreach (self::CRITERIA as $c) {
                $vals = $reviews->pluck($c)->filter()->map(fn ($v) => (float) $v);
                if ($vals->count() > 0) {
                    $criteriaAvg[$c] = $vals->average();
                }
            }
            if (! empty($criteriaAvg)) {
                $topCriterion = array_key_first(
                    array_filter($criteriaAvg, fn ($v) => $v === max($criteriaAvg))
                );
                // Если несколько одинаковых — берём первый по порядку критериев
                if ($topCriterion === null) {
                    $topCriterion = (string) array_key_first($criteriaAvg);
                }
            }
        }

        $agency->update([
            'rating'        => $avg,
            'reviews_count' => $count,
            'top_criterion' => $topCriterion,
        ]);
    }
}
