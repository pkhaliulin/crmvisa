<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Services\MarketplaceService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function __construct(private readonly MarketplaceService $marketplace) {}

    /**
     * GET /api/v1/marketplace
     * Публичный список агентств (без авторизации)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['country', 'visa_type']);
        $agencies = $this->marketplace->listAgencies($filters);

        return ApiResponse::paginated($agencies);
    }

    /**
     * GET /api/v1/marketplace/{slug}
     * Публичный профиль агентства
     */
    public function show(string $slug): JsonResponse
    {
        $profile = $this->marketplace->getPublicProfile($slug);

        if (! $profile) {
            return ApiResponse::notFound('Agency not found');
        }

        return ApiResponse::success([
            'agency'           => [
                'name'     => $profile->agency->name,
                'slug'     => $profile->agency->slug,
                'logo_url' => $profile->agency->logo_url,
            ],
            'description'      => $profile->description,
            'address'          => $profile->address,
            'website'          => $profile->website,
            'countries'        => $profile->countries,
            'visa_types'       => $profile->visa_types,
            'services'         => $profile->services,
            'is_verified'      => $profile->is_verified,
            'is_featured'      => $profile->is_featured,
            'rating'           => $profile->ratingDisplay(),
            'reviews_count'    => $profile->reviews_count,
            'completed_cases'  => $profile->completed_cases,
        ]);
    }

    /**
     * POST /api/v1/marketplace/{slug}/lead
     * Отправить заявку на агентство (публично)
     */
    public function sendLead(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'client_name'  => 'required|string|max:100',
            'client_phone' => 'required|string|max:20',
            'client_email' => 'nullable|email|max:100',
            'country_code' => 'required|string|size:2',
            'visa_type'    => 'nullable|string|max:50',
            'message'      => 'nullable|string|max:1000',
        ]);

        $profile = $this->marketplace->getPublicProfile($slug);

        if (! $profile) {
            return ApiResponse::notFound('Agency not found');
        }

        $lead = $this->marketplace->createLead(array_merge($validated, [
            'agency_id'  => $profile->agency_id,
            'utm_source' => $request->header('X-UTM-Source') ?? $request->query('utm_source'),
        ]));

        return ApiResponse::created(['lead_id' => $lead->id], 'Your request has been sent');
    }

    // -------------------------------------------------------------------------
    // Authenticated: управление профилем агентства
    // -------------------------------------------------------------------------

    /**
     * GET /api/v1/agency/marketplace/profile
     */
    public function myProfile(Request $request): JsonResponse
    {
        $agency  = $request->user()->agency;
        $profile = $this->marketplace->getOrCreateProfile($agency);

        return ApiResponse::success($profile);
    }

    /**
     * PUT /api/v1/agency/marketplace/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'nullable|string|max:2000',
            'address'     => 'nullable|string|max:255',
            'website'     => 'nullable|url|max:255',
            'countries'   => 'nullable|array',
            'countries.*' => 'string|size:2',
            'visa_types'  => 'nullable|array',
            'visa_types.*' => 'string|max:50',
            'services'    => 'nullable|array',
            'services.*'  => 'string|max:100',
        ]);

        try {
            $profile = $this->marketplace->updateProfile($request->user()->agency, $validated);
            return ApiResponse::success($profile, 'Profile updated');
        } catch (\RuntimeException $e) {
            return ApiResponse::forbidden($e->getMessage());
        }
    }

    /**
     * GET /api/v1/agency/marketplace/leads
     */
    public function leads(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        // Проверяем доступ к маркетплейсу
        $plan = \App\Modules\Payment\Models\BillingPlan::find($agency->plan);
        if (! $plan || ! $plan->has_marketplace) {
            return ApiResponse::forbidden('Marketplace access requires Pro or Enterprise plan');
        }

        $leads = $this->marketplace->agencyLeads(
            $agency,
            $request->query('status')
        );

        return ApiResponse::paginated($leads);
    }

    /**
     * PATCH /api/v1/agency/marketplace/leads/{id}/status
     */
    public function updateLeadStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,converted,rejected',
        ]);

        $agency = $request->user()->agency;
        $lead   = \App\Modules\Payment\Models\MarketplaceLead::where('agency_id', $agency->id)
                                                              ->findOrFail($id);

        $lead->update(['status' => $validated['status']]);

        return ApiResponse::success($lead, 'Lead status updated');
    }

    /**
     * GET /api/v1/admin/marketplace/stats
     * Статистика для суперадмина
     */
    public function adminStats(): JsonResponse
    {
        return ApiResponse::success($this->marketplace->stats());
    }
}
