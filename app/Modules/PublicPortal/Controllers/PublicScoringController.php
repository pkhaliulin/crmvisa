<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\AgencyWorkCountry;
use App\Modules\Owner\Models\PortalCountry;
use App\Modules\PublicPortal\Services\PublicScoringService;
use App\Modules\Scoring\Services\ScoringDataAdapter;
use App\Modules\Scoring\Services\UnifiedScoringEngine;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicScoringController extends Controller
{
    public function __construct(
        private PublicScoringService $scoring,
        private UnifiedScoringEngine $unified,
    ) {
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
     * Использует UnifiedScoringEngine (SSOT).
     */
    public function scoreProfile(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');
        $data = ScoringDataAdapter::fromPublicUser($user);
        $result = $this->unified->scoreProfile($data);

        // Red flag описания для UI
        $redFlags = [];
        if ($result['red_flag_multiplier'] < 1.0) {
            $redFlags = $this->scoring->getRedFlagDescriptions($user, $result['red_flag_multiplier']);
        }

        // Локализовать рекомендации
        $recs = $this->localizeRecommendations($result['recommendations']);

        return ApiResponse::success([
            'base_score'          => $result['score'],
            'blocks'              => $result['blocks'],
            'weights'             => $result['weights'],
            'flags'               => $result['flags'],
            'red_flags'           => $redFlags,
            'red_flag_multiplier' => $result['red_flag_multiplier'],
            'recommendations'     => $recs,
            'profile_percent'     => $user->profileCompleteness(),
            'is_blocked'          => $result['is_blocked'],
        ]);
    }

    /**
     * Преобразовать ключи рекомендаций в человекочитаемые тексты.
     */
    private function localizeRecommendations(array $recs): array
    {
        $texts = [
            'employment_needed'     => ['text' => 'Официальное трудоустройство значительно повышает шансы', 'docs' => ['Трудовой договор', 'Приказ о назначении']],
            'income_low'            => ['text' => 'Доход ниже минимального порога — подтвердите доход от $500/мес', 'docs' => ['Справка о доходах с места работы']],
            'income_not_specified'  => ['text' => 'Укажите ежемесячный доход — это значительно повысит скоринг', 'docs' => ['Справка о доходах', 'Банковская выписка за 3-6 месяцев']],
            'official_income_helps' => ['text' => 'Подтвердите официальный источник дохода', 'docs' => ['Справка 2-НДФЛ или аналог', 'Налоговая декларация']],
            'bank_statement_helps'  => ['text' => 'Предоставьте выписку из банка за 3-6 месяцев', 'docs' => ['Банковская выписка']],
            'property_helps'        => ['text' => 'Укажите наличие недвижимости — главный фактор привязанности к родине', 'docs' => ['Свидетельство о праве собственности']],
            'visa_history_empty'    => ['text' => 'Нет истории виз — начните с более лояльных направлений (ОАЭ, Турция)', 'docs' => []],
            'refusal_docs_needed'   => ['text' => 'При наличии отказов важно подготовить полный пакет документов', 'docs' => ['Письмо-объяснение причин отказа', 'Дополнительные подтверждающие документы']],
            'education_helps'       => ['text' => 'Укажите уровень образования — высшее образование повышает доверие', 'docs' => ['Диплом об образовании']],
            'criminal_record_block' => ['text' => 'Судимость — подача визы крайне маловероятна', 'docs' => ['Необходима юридическая консультация']],
        ];

        return array_map(function ($rec) use ($texts) {
            $key = $rec['text'];
            $localized = $texts[$key] ?? null;
            return [
                'type'     => $rec['type'],
                'priority' => $rec['priority'],
                'text'     => $localized['text'] ?? $key,
                'docs'     => $localized['docs'] ?? [],
            ];
        }, $recs);
    }

    /**
     * POST /public/scoring/batch
     * Скоринг по набору стран (для ленивой загрузки в разделе "Страны").
     * Использует UnifiedScoringEngine (SSOT).
     */
    public function scoreBatch(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');
        $input = $request->validate([
            'countries'   => ['required', 'array', 'min:1', 'max:50'],
            'countries.*' => ['string', 'size:2'],
            'visa_type'   => ['sometimes', 'string', 'in:tourist,business,student,work,transit'],
        ]);

        $visaType = $input['visa_type'] ?? 'tourist';
        $data = ScoringDataAdapter::fromPublicUser($user);
        $results = [];
        foreach ($input['countries'] as $cc) {
            $result = $this->unified->scoreForCountry($data, strtoupper($cc), $visaType);
            $result['profile_percent'] = $user->profileCompleteness();
            $results[] = $result;
        }

        usort($results, fn ($a, $b) => $b['score'] - $a['score']);

        return ApiResponse::success($results);
    }

    /**
     * GET /public/scoring/{country}
     * Скоринг по конкретной стране (требует авторизацию).
     * Использует UnifiedScoringEngine (SSOT).
     */
    public function scoreCountry(Request $request, string $country): JsonResponse
    {
        $user = $request->get('_public_user');
        $visaType = $request->query('visa_type', 'tourist');
        if (! in_array($visaType, ['tourist', 'business', 'student', 'work', 'transit'])) {
            $visaType = 'tourist';
        }

        $data = ScoringDataAdapter::fromPublicUser($user);
        $result = $this->unified->scoreForCountry($data, strtoupper($country), $visaType);
        $result['profile_percent'] = $user->profileCompleteness();

        return ApiResponse::success($result);
    }

    /**
     * GET /public/scoring
     * Скоринг по всем странам для сравнения.
     * Использует UnifiedScoringEngine (SSOT).
     */
    public function scoreAll(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');
        $visaType = $request->query('visa_type', 'tourist');
        if (! in_array($visaType, ['tourist', 'business', 'student', 'work', 'transit'])) {
            $visaType = 'tourist';
        }

        $data = ScoringDataAdapter::fromPublicUser($user);

        // Получить список стран
        $countries = DB::table('portal_countries')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('country_code')
            ->toArray();

        if (empty($countries)) {
            $countries = ['DE', 'ES', 'FR', 'IT', 'PL', 'CZ', 'GB', 'US', 'CA', 'KR', 'AE'];
        }

        $results = collect($countries)
            ->map(function ($cc) use ($data, $visaType, $user) {
                $result = $this->unified->scoreForCountry($data, $cc, $visaType);
                $result['profile_percent'] = $user->profileCompleteness();
                return $result;
            })
            ->sortByDesc('score')
            ->values()
            ->toArray();

        return ApiResponse::success([
            'scores'          => $results,
            'profile_percent' => $user->profileCompleteness(),
        ]);
    }
}
