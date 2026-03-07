<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\AgencyWorkCountry;
use App\Modules\Owner\Models\PortalCountry;
use App\Modules\PublicPortal\Services\PublicScoringService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicScoringController extends Controller
{
    public function __construct(private PublicScoringService $scoring)
    {
        // Публичные эндпоинты без auth.public — нужно открыть RLS для мультитенантных таблиц
        DB::statement("SET app.is_public_user = 'true'");
    }

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

        $countries = $query->get();

        // Подсчитываем количество активных агентств для каждой страны
        $countryCodes = $countries->pluck('country_code')->toArray();
        $agencyCounts = AgencyWorkCountry::where('is_active', true)
            ->whereIn('country_code', $countryCodes)
            ->whereHas('agency', fn ($q) => $q->where('is_active', true))
            ->selectRaw('country_code, count(distinct agency_id) as cnt')
            ->groupBy('country_code')
            ->pluck('cnt', 'country_code');

        $countries->each(function ($c) use ($agencyCounts) {
            $c->agencies_count = $agencyCounts[$c->country_code] ?? 0;
            $c->has_agencies = ($agencyCounts[$c->country_code] ?? 0) > 0;
        });

        return ApiResponse::success($countries);
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

        // Количество активных агентств для этой страны
        $count = AgencyWorkCountry::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->whereHas('agency', fn ($q) => $q->where('is_active', true))
            ->count();
        $country->agencies_count = $count;
        $country->has_agencies = $count > 0;

        return ApiResponse::success($country);
    }

    /**
     * GET /public/served-countries
     * Список стран+виз, по которым работает хотя бы одно агентство.
     */
    public function servedCountries(): JsonResponse
    {
        $served = AgencyWorkCountry::where('is_active', true)
            ->whereHas('agency', fn ($q) => $q->where('is_active', true))
            ->selectRaw('country_code, count(distinct agency_id) as agencies_count')
            ->groupBy('country_code')
            ->get()
            ->keyBy('country_code');

        return ApiResponse::success($served->map(fn ($row) => [
            'country_code'   => $row->country_code,
            'agencies_count' => (int) $row->agencies_count,
        ])->values());
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
