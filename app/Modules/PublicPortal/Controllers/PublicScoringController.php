<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicPortal\Services\PublicScoringService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicScoringController extends Controller
{
    // Страны, доступные для скоринга (где для граждан УЗ нужна виза)
    public const COUNTRIES = [
        ['code' => 'DE', 'name' => 'Германия',       'flag' => '🇩🇪', 'visa_type' => 'tourist'],
        ['code' => 'ES', 'name' => 'Испания',         'flag' => '🇪🇸', 'visa_type' => 'tourist'],
        ['code' => 'FR', 'name' => 'Франция',         'flag' => '🇫🇷', 'visa_type' => 'tourist'],
        ['code' => 'IT', 'name' => 'Италия',          'flag' => '🇮🇹', 'visa_type' => 'tourist'],
        ['code' => 'PL', 'name' => 'Польша',          'flag' => '🇵🇱', 'visa_type' => 'tourist'],
        ['code' => 'CZ', 'name' => 'Чехия',           'flag' => '🇨🇿', 'visa_type' => 'tourist'],
        ['code' => 'GB', 'name' => 'Великобритания',  'flag' => '🇬🇧', 'visa_type' => 'tourist'],
        ['code' => 'US', 'name' => 'США',             'flag' => '🇺🇸', 'visa_type' => 'tourist'],
        ['code' => 'CA', 'name' => 'Канада',          'flag' => '🇨🇦', 'visa_type' => 'tourist'],
        ['code' => 'KR', 'name' => 'Южная Корея',     'flag' => '🇰🇷', 'visa_type' => 'tourist'],
    ];

    public function __construct(private PublicScoringService $scoring) {}

    /**
     * GET /public/countries
     * Список стран для лендинга (без авторизации).
     */
    public function countries(): JsonResponse
    {
        return ApiResponse::success(self::COUNTRIES);
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
