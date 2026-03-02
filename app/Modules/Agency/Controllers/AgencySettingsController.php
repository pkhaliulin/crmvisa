<?php

namespace App\Modules\Agency\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Agency\Models\AgencyWorkCountry;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $country = AgencyWorkCountry::updateOrCreate(
            ['agency_id' => $agencyId, 'country_code' => strtoupper($data['country_code'])],
            [
                'visa_types' => $data['visa_types'] ?? [],
                'is_active'  => true,
            ]
        );

        return ApiResponse::created($country);
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
