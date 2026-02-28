<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Models\AgencyProfile;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\MarketplaceLead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MarketplaceService
{
    /**
     * Публичный список агентств для маркетплейса
     */
    public function listAgencies(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AgencyProfile::with('agency')
                              ->visible()
                              ->orderByDesc('is_featured')
                              ->orderByDesc('rating_avg');

        if (! empty($filters['country'])) {
            $query->whereJsonContains('countries', $filters['country']);
        }

        if (! empty($filters['visa_type'])) {
            $query->whereJsonContains('visa_types', $filters['visa_type']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Публичный профиль агентства
     */
    public function getPublicProfile(string $agencySlug): ?AgencyProfile
    {
        return AgencyProfile::with('agency')
                            ->whereHas('agency', fn ($q) => $q->where('slug', $agencySlug)->where('is_active', true))
                            ->where('is_visible', true)
                            ->first();
    }

    /**
     * Получить или создать профиль агентства на маркетплейсе
     */
    public function getOrCreateProfile(Agency $agency): AgencyProfile
    {
        return AgencyProfile::firstOrCreate(
            ['agency_id' => $agency->id],
            [
                'countries'  => [],
                'visa_types' => [],
                'services'   => [],
                'is_visible' => false,
            ]
        );
    }

    /**
     * Обновить профиль агентства (только если тариф позволяет)
     */
    public function updateProfile(Agency $agency, array $data): AgencyProfile
    {
        $plan = BillingPlan::find($agency->plan);

        if (! $plan || ! $plan->has_marketplace) {
            throw new \RuntimeException('Marketplace access requires Pro or Enterprise plan.');
        }

        $profile = $this->getOrCreateProfile($agency);

        $allowed = ['description', 'address', 'website', 'countries', 'visa_types', 'services'];
        $profile->update(array_intersect_key($data, array_flip($allowed)));

        // Показываем в листинге только если профиль верифицирован
        if ($profile->is_verified) {
            $profile->update(['is_visible' => true]);
        }

        return $profile->fresh();
    }

    /**
     * Входящий лид с маркетплейса
     */
    public function createLead(array $data): MarketplaceLead
    {
        return MarketplaceLead::create([
            'agency_id'    => $data['agency_id'],
            'client_name'  => $data['client_name'],
            'client_phone' => $data['client_phone'],
            'client_email' => $data['client_email'] ?? null,
            'country_code' => $data['country_code'],
            'visa_type'    => $data['visa_type'] ?? null,
            'message'      => $data['message'] ?? null,
            'status'       => 'new',
            'utm_source'   => $data['utm_source'] ?? null,
        ]);
    }

    /**
     * Список лидов агентства
     */
    public function agencyLeads(Agency $agency, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = MarketplaceLead::where('agency_id', $agency->id)->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage);
    }

    /**
     * Конвертировать лид в заявку
     */
    public function convertLead(MarketplaceLead $lead, string $caseId): void
    {
        $lead->update([
            'status'            => 'converted',
            'converted_case_id' => $caseId,
        ]);
    }

    /**
     * Статистика для маркетплейса (суперадмин)
     */
    public function stats(): array
    {
        return [
            'total_agencies' => AgencyProfile::where('is_visible', true)->count(),
            'verified'       => AgencyProfile::where('is_verified', true)->count(),
            'featured'       => AgencyProfile::where('is_featured', true)->count(),
            'leads_today'    => MarketplaceLead::whereDate('created_at', today())->count(),
            'leads_total'    => MarketplaceLead::count(),
            'converted'      => MarketplaceLead::where('status', 'converted')->count(),
        ];
    }
}
