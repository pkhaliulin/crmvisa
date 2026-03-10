<?php

namespace App\Modules\Knowledge\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Enums\Plan;
use App\Modules\Agency\Models\Agency;
use App\Modules\Knowledge\Models\KnowledgeArticle;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicKnowledgeController extends Controller
{
    /**
     * Проверка тарифа Enterprise.
     * Глобальная БЗ доступна только на Enterprise.
     */
    private function checkAccess(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->agency_id) {
            return ApiResponse::forbidden('Нет доступа к базе знаний.');
        }

        // Суперадмин — всегда доступ
        if ($user->role === 'superadmin') {
            return null;
        }

        $agency = Agency::find($user->agency_id);

        if (! $agency || $agency->plan !== Plan::Enterprise) {
            return ApiResponse::forbidden('База знаний доступна только на тарифе Enterprise.');
        }

        return null;
    }

    public function index(Request $request): JsonResponse
    {
        if ($denied = $this->checkAccess($request)) {
            return $denied;
        }

        $query = KnowledgeArticle::published()
            ->orderBy('sort_order')
            ->orderByDesc('published_at');

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }
        if ($request->filled('country_code')) {
            $query->byCountry($request->country_code);
        }
        if ($request->filled('visa_type')) {
            $query->byVisaType($request->visa_type);
        }
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ilike', $search)
                  ->orWhere('content', 'ilike', $search)
                  ->orWhere('title_uz', 'ilike', $search);
            });
        }

        return ApiResponse::paginated(
            $query->paginate($request->integer('per_page', 20))
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        if ($denied = $this->checkAccess($request)) {
            return $denied;
        }

        $article = KnowledgeArticle::published()->findOrFail($id);
        $article->incrementViews();

        return ApiResponse::success($article);
    }

    public function byCountry(Request $request, string $code): JsonResponse
    {
        if ($denied = $this->checkAccess($request)) {
            return $denied;
        }

        $articles = KnowledgeArticle::published()
            ->byCountry($code)
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->get();

        // Группировка по категориям
        $grouped = $articles->groupBy('category')->map(function ($items) {
            return $items->values();
        });

        return ApiResponse::success([
            'country_code' => strtoupper($code),
            'total'        => $articles->count(),
            'categories'   => $grouped,
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        if ($denied = $this->checkAccess($request)) {
            return $denied;
        }

        return ApiResponse::success(KnowledgeArticle::categories());
    }
}
