<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Owner\Models\CountryVisaTypeSetting;
use App\Modules\Owner\Models\PortalCountry;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CountryDetailController extends Controller
{
    // =========================================================================
    // Детальная страница страны (superadmin)
    // =========================================================================

    public function countryShow(string $code): JsonResponse
    {
        $country = PortalCountry::where('country_code', strtoupper($code))->firstOrFail();

        $stats = [
            'total_cases'      => DB::table('cases')->where('country_code', $code)->count(),
            'active_cases'     => DB::table('cases')->where('country_code', $code)->whereNotIn('stage', ['result'])->count(),
            'total_leads'      => DB::table('public_leads')->where('country_code', $code)->count(),
            'visa_types_count' => CountryVisaTypeSetting::where('country_code', $code)->where('is_active', true)->count(),
            'documents_count'  => DB::table('country_visa_requirements')->where('country_code', $code)->count(),
        ];

        $visaSettings = CountryVisaTypeSetting::where('country_code', $code)
            ->orderBy('visa_type')
            ->get();

        return ApiResponse::success([
            'country'       => $country,
            'stats'         => $stats,
            'visa_settings' => $visaSettings,
        ]);
    }

    // =========================================================================
    // Посольство
    // =========================================================================

    public function updateEmbassy(Request $request, string $code): JsonResponse
    {
        $country = PortalCountry::where('country_code', strtoupper($code))->firstOrFail();

        $data = $request->validate([
            'embassy_name'         => 'sometimes|nullable|string|max:255',
            'embassy_address'      => 'sometimes|nullable|string|max:500',
            'embassy_city'         => 'sometimes|nullable|string|max:100',
            'embassy_phone'        => 'sometimes|nullable|string|max:50',
            'embassy_email'        => 'sometimes|nullable|email|max:255',
            'embassy_website'      => 'sometimes|nullable|url|max:500',
            'embassy_description'  => 'sometimes|nullable|string|max:5000',
            'embassy_rules'        => 'sometimes|nullable|string|max:5000',
            'appointment_url'      => 'sometimes|nullable|url|max:500',
            'submission_type'      => 'sometimes|in:embassy_direct,visa_center,both,online',
            'visa_center_name'     => 'sometimes|nullable|string|max:255',
            'visa_center_address'  => 'sometimes|nullable|string|max:500',
            'visa_center_phone'    => 'sometimes|nullable|string|max:50',
            'visa_center_website'  => 'sometimes|nullable|url|max:500',
            'latitude'             => 'sometimes|nullable|numeric|between:-90,90',
            'longitude'            => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        $country->update($data);
        Cache::forget('portal_countries_weights');

        return ApiResponse::success($country->fresh());
    }

    // =========================================================================
    // Настройки типов виз (per-visa-type)
    // =========================================================================

    public function visaSettings(string $code): JsonResponse
    {
        $settings = CountryVisaTypeSetting::where('country_code', strtoupper($code))
            ->orderBy('visa_type')
            ->get();

        return ApiResponse::success($settings);
    }

    public function visaSettingStore(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'visa_type'              => 'required|string|max:50',
            'preparation_days'       => 'sometimes|integer|min:0|max:365',
            'appointment_wait_days'  => 'sometimes|integer|min:0|max:365',
            'processing_days_min'    => 'sometimes|integer|min:0|max:365',
            'processing_days_max'    => 'sometimes|integer|min:0|max:365',
            'processing_days_avg'    => 'sometimes|integer|min:0|max:365',
            'buffer_days'            => 'sometimes|integer|min:0|max:365',
            'parallel_docs_allowed'  => 'sometimes|boolean',
            'avg_refusal_rate'       => 'sometimes|nullable|numeric|min:0|max:100',
            'biometrics_required'    => 'sometimes|boolean',
            'personal_visit_required'=> 'sometimes|boolean',
            'interview_required'     => 'sometimes|boolean',
            'appointment_pattern'    => 'sometimes|in:fixed_schedule,random_wave,daily_slots,no_appointment',
            'appointment_notes'      => 'sometimes|nullable|string|max:2000',
            'consular_fee_usd'       => 'sometimes|nullable|numeric|min:0',
            'service_fee_usd'        => 'sometimes|nullable|numeric|min:0',
            'is_active'              => 'sometimes|boolean',
            'notes'                  => 'sometimes|nullable|string|max:5000',
        ]);

        $data['country_code'] = strtoupper($code);

        $setting = CountryVisaTypeSetting::create($data);

        return ApiResponse::success($setting, 'Настройки типа визы созданы', 201);
    }

    public function visaSettingUpdate(Request $request, string $code, string $id): JsonResponse
    {
        $setting = CountryVisaTypeSetting::where('country_code', strtoupper($code))
            ->findOrFail($id);

        $data = $request->validate([
            'preparation_days'       => 'sometimes|integer|min:0|max:365',
            'appointment_wait_days'  => 'sometimes|integer|min:0|max:365',
            'processing_days_min'    => 'sometimes|integer|min:0|max:365',
            'processing_days_max'    => 'sometimes|integer|min:0|max:365',
            'processing_days_avg'    => 'sometimes|integer|min:0|max:365',
            'buffer_days'            => 'sometimes|integer|min:0|max:365',
            'parallel_docs_allowed'  => 'sometimes|boolean',
            'avg_refusal_rate'       => 'sometimes|nullable|numeric|min:0|max:100',
            'biometrics_required'    => 'sometimes|boolean',
            'personal_visit_required'=> 'sometimes|boolean',
            'interview_required'     => 'sometimes|boolean',
            'appointment_pattern'    => 'sometimes|in:fixed_schedule,random_wave,daily_slots,no_appointment',
            'appointment_notes'      => 'sometimes|nullable|string|max:2000',
            'consular_fee_usd'       => 'sometimes|nullable|numeric|min:0',
            'service_fee_usd'        => 'sometimes|nullable|numeric|min:0',
            'is_active'              => 'sometimes|boolean',
            'notes'                  => 'sometimes|nullable|string|max:5000',
        ]);

        $setting->update($data);

        return ApiResponse::success($setting->fresh());
    }

    public function visaSettingDestroy(string $code, string $id): JsonResponse
    {
        $setting = CountryVisaTypeSetting::where('country_code', strtoupper($code))
            ->findOrFail($id);

        $setting->delete();

        return ApiResponse::success(null, 'Настройки типа визы удалены');
    }

    // =========================================================================
    // Документы (country_visa_requirements)
    // =========================================================================

    public function requirements(string $code): JsonResponse
    {
        $requirements = DB::table('country_visa_requirements')
            ->leftJoin('document_templates', 'country_visa_requirements.document_template_id', '=', 'document_templates.id')
            ->where('country_visa_requirements.country_code', strtoupper($code))
            ->select(
                'country_visa_requirements.*',
                'document_templates.name as template_name',
                'document_templates.category as template_category',
            )
            ->orderBy('country_visa_requirements.visa_type')
            ->orderBy('document_templates.category')
            ->get();

        return ApiResponse::success($requirements);
    }

    // =========================================================================
    // Скоринг
    // =========================================================================

    public function scoring(string $code): JsonResponse
    {
        $country = PortalCountry::where('country_code', strtoupper($code))->firstOrFail();

        $weights = DB::table('scoring_country_weights')
            ->where('country_code', strtoupper($code))
            ->get();

        $thresholds = DB::table('scoring_financial_thresholds')
            ->where('country_code', strtoupper($code))
            ->first();

        return ApiResponse::success([
            'country'    => $country->only([
                'country_code', 'weight_finance', 'weight_ties', 'weight_travel', 'weight_profile',
                'weight_finances', 'weight_visa_history', 'weight_social_ties',
                'min_monthly_income_usd', 'min_score', 'risk_level', 'destination_score_bonus',
            ]),
            'weights'    => $weights,
            'thresholds' => $thresholds,
        ]);
    }

    public function updateScoring(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'weight_finance'         => 'sometimes|numeric|min:0|max:1',
            'weight_ties'            => 'sometimes|numeric|min:0|max:1',
            'weight_travel'          => 'sometimes|numeric|min:0|max:1',
            'weight_profile'         => 'sometimes|numeric|min:0|max:1',
            'weight_finances'        => 'sometimes|numeric|min:0|max:1',
            'weight_visa_history'    => 'sometimes|numeric|min:0|max:1',
            'weight_social_ties'     => 'sometimes|numeric|min:0|max:1',
            'min_monthly_income_usd' => 'sometimes|integer|min:0',
            'min_score'              => 'sometimes|integer|min:0|max:100',
            'risk_level'             => 'sometimes|in:low,medium,high',
            'destination_score_bonus'=> 'sometimes|integer|min:-50|max:50',
        ]);

        DB::table('portal_countries')
            ->where('country_code', strtoupper($code))
            ->update(array_merge($data, ['updated_at' => now()]));

        Cache::forget('portal_countries_weights');
        Cache::forget('portal_countries_codes');

        $country = PortalCountry::where('country_code', strtoupper($code))->first();

        return ApiResponse::success($country);
    }

    // =========================================================================
    // Аналитика (заглушка)
    // =========================================================================

    public function stats(string $code): JsonResponse
    {
        $cc = strtoupper($code);

        $monthlyLeads = DB::table('public_leads')
            ->where('country_code', $cc)
            ->selectRaw("to_char(created_at, 'YYYY-MM') as month, count(*) as count")
            ->groupByRaw("to_char(created_at, 'YYYY-MM')")
            ->orderBy('month')
            ->limit(12)
            ->get();

        $casesByStage = DB::table('cases')
            ->where('country_code', $cc)
            ->selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->get();

        $avgProcessingDays = DB::table('cases')
            ->where('country_code', $cc)
            ->where('stage', 'result')
            ->selectRaw('avg(extract(epoch from (updated_at - created_at)) / 86400) as avg_days')
            ->value('avg_days');

        return ApiResponse::success([
            'monthly_leads'       => $monthlyLeads,
            'cases_by_stage'      => $casesByStage,
            'avg_processing_days' => $avgProcessingDays ? round($avgProcessingDays, 1) : null,
        ]);
    }

    // =========================================================================
    // Публичный эндпоинт для CRM (агентства)
    // =========================================================================

    public function visaSettingsPublic(string $code): JsonResponse
    {
        $settings = CountryVisaTypeSetting::where('country_code', strtoupper($code))
            ->where('is_active', true)
            ->orderBy('visa_type')
            ->get([
                'id', 'country_code', 'visa_type',
                'preparation_days', 'appointment_wait_days',
                'processing_days_min', 'processing_days_max', 'processing_days_avg',
                'buffer_days', 'min_days_before_departure', 'recommended_days_before_departure',
                'biometrics_required', 'personal_visit_required', 'interview_required',
                'consular_fee_usd', 'service_fee_usd',
            ]);

        return ApiResponse::success($settings);
    }
}
