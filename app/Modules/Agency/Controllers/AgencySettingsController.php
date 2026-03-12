<?php

namespace App\Modules\Agency\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyWorkCountry;
use App\Modules\Payment\Models\BillingPlan;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgencySettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        if (! $request->user()->agency_id) {
            return ApiResponse::error('No agency assigned to this account.', null, 403);
        }

        $agency = Agency::with('workCountries')->findOrFail($request->user()->agency_id);

        return ApiResponse::success($agency);
    }

    public function update(Request $request): JsonResponse
    {
        if (! $request->user()->agency_id) {
            return ApiResponse::error('No agency assigned to this account.', null, 403);
        }

        // Нормализуем URL — добавляем https:// если протокол не указан
        if ($request->filled('website_url')) {
            $url = trim($request->input('website_url'));
            if (! preg_match('#^https?://#i', $url)) {
                $url = 'https://' . $url;
            }
            $request->merge(['website_url' => $url]);
        }

        $data = $request->validate([
            'managers_see_all_cases' => ['sometimes', 'boolean'],
            'lead_assignment_mode'   => ['sometimes', 'in:manual,round_robin,by_workload,by_country'],
            'name'                   => ['sometimes', 'nullable', 'string', 'max:255'],
            'description'            => ['sometimes', 'nullable', 'string', 'max:2000'],
            'description_uz'         => ['sometimes', 'nullable', 'string', 'max:2000'],
            'experience_years'       => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'website_url'            => ['sometimes', 'nullable', 'regex:/^https?:\/\/[a-zA-Z0-9][a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}/i', 'max:255'],
            'address'                => ['sometimes', 'nullable', 'string', 'max:500'],
            'city'                   => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone'                  => ['sometimes', 'nullable', 'string', 'max:30'],
            'email'                  => ['sometimes', 'nullable', 'email', 'max:255'],
            'latitude'               => ['sometimes', 'nullable', 'numeric'],
            'longitude'              => ['sometimes', 'nullable', 'numeric'],
        ]);

        $agency = Agency::findOrFail($request->user()->agency_id);
        $agency->update($data);

        return ApiResponse::success($agency->fresh('workCountries'));
    }

    public function workCountries(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $countries = AgencyWorkCountry::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->get();

        return ApiResponse::success($countries);
    }

    public function addWorkCountry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_code' => ['required', 'string', 'size:2'],
            'visa_types'   => ['sometimes', 'array'],
            'visa_types.*' => ['string', 'max:50'],
        ]);

        $agencyId = $request->user()->agency_id;

        try {
            $country = AgencyWorkCountry::updateOrCreate(
                ['agency_id' => $agencyId, 'country_code' => strtoupper($data['country_code'])],
                [
                    'visa_types' => $data['visa_types'] ?? [],
                    'is_active'  => true,
                ]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            $country = AgencyWorkCountry::where('agency_id', $agencyId)
                ->where('country_code', strtoupper($data['country_code']))
                ->first();
        }

        return ApiResponse::created($country);
    }

    public function generateApiKey(Request $request): JsonResponse
    {
        $agency = Agency::findOrFail($request->user()->agency_id);

        $raw = 'vbk_' . Str::random(48);
        $agency->update([
            'api_key'              => hash('sha256', $raw),
            'api_key_generated_at' => now(),
        ]);

        return ApiResponse::success([
            'api_key'      => $raw,
            'generated_at' => $agency->api_key_generated_at->toIso8601String(),
            'warning'      => 'Сохраните ключ — он показывается только один раз.',
        ]);
    }

    public function apiKeyInfo(Request $request): JsonResponse
    {
        $agency = Agency::findOrFail($request->user()->agency_id);

        return ApiResponse::success([
            'has_key'      => (bool) $agency->api_key,
            'generated_at' => $agency->api_key_generated_at?->toIso8601String(),
        ]);
    }

    public function featureStatus(Request $request): JsonResponse
    {
        if (! $request->user()->agency_id) {
            return ApiResponse::error('No agency assigned to this account.', null, 403);
        }

        $agency = Agency::findOrFail($request->user()->agency_id);
        $planSlug = $agency->effectivePlan();
        $plan = BillingPlan::find($planSlug);

        if (! $plan) {
            return ApiResponse::error('Billing plan not found.', null, 404);
        }

        $hasActiveWorkCountries = $agency->workCountries()->where('is_active', true)->exists();
        $hasServicePackages = $agency->packages()->exists();
        $hasDescription = ! empty($agency->description);
        $hasLogoUrl = ! empty($agency->logo_url);
        $hasTelegramBotToken = ! empty($agency->telegram_bot_token);
        $hasTelegramBotUsername = ! empty($agency->telegram_bot_username);
        $hasCustomDomain = ! empty($agency->custom_domain);

        $features = [
            'marketplace' => [
                'available' => $plan->has_marketplace,
                'activated' => $plan->has_marketplace && $hasDescription && $hasActiveWorkCountries && $hasServicePackages,
                'requirements' => [
                    'description' => $hasDescription,
                    'work_countries' => $hasActiveWorkCountries,
                    'service_packages' => $hasServicePackages,
                ],
            ],
            'white_label' => [
                'available' => $plan->has_white_label,
                'activated' => $plan->has_white_label && $hasLogoUrl && $hasTelegramBotToken && $hasTelegramBotUsername,
                'requirements' => [
                    'logo_url' => $hasLogoUrl,
                    'telegram_bot_token' => $hasTelegramBotToken,
                    'telegram_bot_username' => $hasTelegramBotUsername,
                ],
            ],
            'api_access' => [
                'available' => $plan->has_api_access,
                'activated' => $plan->has_api_access && ! empty($agency->api_key),
                'requirements' => [
                    'api_key' => ! empty($agency->api_key),
                ],
            ],
            'analytics' => [
                'available' => $plan->has_analytics,
                'activated' => $plan->has_analytics,
                'requirements' => [],
            ],
            'custom_domain' => [
                'available' => $plan->has_custom_domain,
                'activated' => $plan->has_custom_domain && $hasCustomDomain,
                'requirements' => [
                    'custom_domain' => $hasCustomDomain,
                ],
            ],
            'priority_support' => [
                'available' => $plan->has_priority_support,
                'activated' => $plan->has_priority_support,
                'requirements' => [],
            ],
        ];

        return ApiResponse::success([
            'features' => $features,
            'current_plan' => $planSlug,
            'plan_name' => $plan->name,
        ]);
    }

    public function updateBranding(Request $request): JsonResponse
    {
        if (! $request->user()->agency_id) {
            return ApiResponse::error('No agency assigned to this account.', null, 403);
        }

        $data = $request->validate([
            'logo_url'               => ['sometimes', 'nullable', 'string', 'max:500'],
            'primary_color'          => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color'        => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'favicon_url'            => ['sometimes', 'nullable', 'string', 'max:500'],
            'telegram_bot_token'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'telegram_bot_username'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'custom_domain'          => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $agency = Agency::findOrFail($request->user()->agency_id);
        $agency->update($data);

        return ApiResponse::success($agency->fresh());
    }

    public function removeWorkCountry(Request $request, string $cc): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        AgencyWorkCountry::where('agency_id', $agencyId)
            ->where('country_code', strtoupper($cc))
            ->delete();

        return ApiResponse::success(null, 'Country removed.');
    }
}
