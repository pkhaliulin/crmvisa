<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Owner\Models\PortalCountry;
use App\Modules\PublicPortal\Services\PublicScoringService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicScoringController extends Controller
{
    public function __construct(private PublicScoringService $scoring) {}

    /**
     * GET /public/countries
     * Список стран для лендинга (без авторизации).
     * Фильтры: ?visa_regime=visa_free&continent=Europe
     */
    public function countries(Request $request): JsonResponse
    {
        $query = PortalCountry::where('is_active', true)
            ->when($request->visa_regime, fn ($q, $r) => $q->byRegime($r))
            ->when($request->continent, fn ($q, $c) => $q->byContinent($c))
            ->orderBy('sort_order');

        return ApiResponse::success($query->get());
    }

    /**
     * GET /public/countries/{code}
     * Просмотр страны + инкремент view_count.
     */
    public function countryView(string $code): JsonResponse
    {
        $country = PortalCountry::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->firstOrFail();

        $country->increment('view_count');

        return ApiResponse::success($country);
    }

    /**
     * GET /public/scoring/{country}
     * Скоринг по конкретной стране (требует авторизацию).
     */
    public function scoreCountry(Request $request, string $country): JsonResponse
    {
        $user     = $request->get('_public_user');
        $visaType = $request->query('visa_type', 'tourist');
        $result   = $this->scoring->score($user, strtoupper($country), $visaType);

        return ApiResponse::success($result);
    }

    /**
     * GET /public/scoring
     * Скоринг по всем странам для сравнения.
     */
    public function scoreAll(Request $request): JsonResponse
    {
        $user     = $request->get('_public_user');
        $visaType = $request->query('visa_type', 'tourist');
        $results  = $this->scoring->scoreAll($user, $visaType);

        return ApiResponse::success([
            'scores'          => $results,
            'profile_percent' => $user->profileCompleteness(),
        ]);
    }
}
