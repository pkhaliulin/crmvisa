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

        // Доступные типы виз из пакетов агентств
        $visaTypes = \DB::table('agency_service_packages')
            ->where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->whereIn('agency_id', function ($q) {
                $q->select('id')->from('agencies')
                  ->where('is_active', true)
                  ->whereNull('blocked_at');
            })
            ->distinct()
            ->pluck('visa_type')
            ->filter()
            ->values();
        $country->available_visa_types = $visaTypes;

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

        // Разрешённые типы виз из portal_visa_types
        $allowedTypes = \DB::table('portal_visa_types')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('slug')
            ->toArray();

        // Реальные типы виз из пакетов агентств по каждой стране
        $packageTypes = \DB::table('agency_service_packages')
            ->where('is_active', true)
            ->whereNotNull('visa_type')
            ->whereIn('agency_id', function ($q) {
                $q->select('id')->from('agencies')
                  ->where('is_active', true)
                  ->whereNull('blocked_at');
            })
            ->selectRaw('country_code, array_agg(distinct visa_type) as types')
            ->groupBy('country_code')
            ->pluck('types', 'country_code')
            ->map(fn ($v) => str_getcsv(trim($v, '{}')));

        return ApiResponse::success($served->map(function ($row) use ($allowedTypes, $packageTypes) {
            $countryTypes = $packageTypes[$row->country_code] ?? [];
            // Пересечение: только разрешённые суперадмином + реально доступные у агентств
            $visaTypes = $countryTypes
                ? array_values(array_intersect($allowedTypes, $countryTypes))
                : [];

            return [
                'country_code'   => $row->country_code,
                'agencies_count' => (int) $row->agencies_count,
                'visa_types'     => $visaTypes,
            ];
        })->filter(fn ($item) => count($item['visa_types']) > 0)->values());
    }

    /**
     * GET /public/scoring/profile
     * Базовый скоринговый профиль клиента (без привязки к стране).
     */
    public function scoreProfile(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        // Calculate base blocks
        $blocks = [
            'finances'     => $this->scoring->calcFinances($user),
            'visa_history' => $this->scoring->calcVisaHistory($user),
            'social_ties'  => $this->scoring->calcSocialTies($user),
        ];

        $redFlagMultiplier = $this->scoring->applyRedFlags($user);
        $redFlags = [];
        if ($redFlagMultiplier < 1.0) {
            $redFlags = $this->scoring->getRedFlagDescriptions($user, $redFlagMultiplier);
        }

        // Base score (average of 3 blocks, weighted)
        $baseScore = (int) round(
            $blocks['finances'] * 0.30 +
            $blocks['visa_history'] * 0.40 +
            $blocks['social_ties'] * 0.30
        );
        $baseScore = (int) round($baseScore * $redFlagMultiplier);
        $baseScore = max(5, min(100, $baseScore));

        // Recommendations
        $recs = $this->scoring->profileRecommendations($user, $blocks);

        return ApiResponse::success([
            'base_score'          => $baseScore,
            'blocks'              => $blocks,
            'red_flags'           => $redFlags,
            'red_flag_multiplier' => $redFlagMultiplier,
            'recommendations'     => $recs,
            'profile_percent'     => $user->profileCompleteness(),
        ]);
    }

    /**
     * POST /public/scoring/batch
     * Скоринг по набору стран (для ленивой загрузки в разделе "Страны").
     */
    public function scoreBatch(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');
        $data = $request->validate([
            'countries'   => ['required', 'array', 'min:1', 'max:50'],
            'countries.*' => ['string', 'size:2'],
            'visa_type'   => ['sometimes', 'string', 'in:tourist,business,student,work,transit'],
        ]);

        $visaType = $data['visa_type'] ?? 'tourist';
        $results = [];
        foreach ($data['countries'] as $cc) {
            $results[] = $this->scoring->score($user, strtoupper($cc), $visaType);
        }

        // Sort by score desc
        usort($results, fn ($a, $b) => $b['score'] - $a['score']);

        return ApiResponse::success($results);
    }

    /**
     * GET /public/scoring/{country}
     * Скоринг по конкретной стране (требует авторизацию).
     */
    public function scoreCountry(Request $request, string $country): JsonResponse
    {
        $user     = $request->get('_public_user');
        $visaType = $request->query('visa_type', 'tourist');
        if (! in_array($visaType, ['tourist', 'business', 'student', 'work', 'transit'])) {
            $visaType = 'tourist';
        }
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
        if (! in_array($visaType, ['tourist', 'business', 'student', 'work', 'transit'])) {
            $visaType = 'tourist';
        }
        $results  = $this->scoring->scoreAll($user, $visaType);

        return ApiResponse::success([
            'scores'          => $results,
            'profile_percent' => $user->profileCompleteness(),
        ]);
    }
}
