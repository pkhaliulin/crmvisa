<?php

namespace App\Modules\Case\Services;

use App\Modules\Agency\Models\AgencyWorkCountry;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Owner\Models\PortalCountry;
use Illuminate\Support\Facades\DB;

class DashboardCountryService
{
    public function getCountryData(string $agencyId, ?string $dateFrom, ?string $dateTo): array
    {
        $excludeUnpaid = DashboardMetricsService::excludeUnpaidScope();

        $inPeriod = fn ($q) => $dateFrom
            ? $q->whereBetween('cases.created_at', [$dateFrom, $dateTo])
            : $q;

        // Топ стран
        $topCountries = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->select('country_code', DB::raw('COUNT(*) as total'))
            ->groupBy('country_code')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Источники лидов
        $byLeadSource = VisaCase::from('cases')
            ->where('cases.agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where($inPeriod)
            ->leftJoin('clients', 'clients.id', '=', 'cases.client_id')
            ->selectRaw("COALESCE(cases.lead_source, clients.source, 'direct') as source, COUNT(*) as count")
            ->groupByRaw("COALESCE(cases.lead_source, clients.source, 'direct')")
            ->orderByDesc('count')
            ->get();

        // Динамика за период
        $trendFrom = $dateFrom ?? now()->subDays(30)->toDateString();
        $dailyTrend = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('created_at', '>=', $trendFrom)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as created, COUNT(CASE WHEN stage='result' THEN 1 END) as completed")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // По приоритетам
        $byPriority = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority');

        // Популярные страны платформы
        $workCodes = AgencyWorkCountry::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->pluck('country_code')
            ->toArray();

        $popularCountries = PortalCountry::where('is_active', true)
            ->whereIn('visa_regime', ['visa_required', 'evisa'])
            ->orderByRaw('COALESCE(lead_count, 0) + COALESCE(view_count, 0) DESC')
            ->limit(15)
            ->select('country_code', 'name', 'flag_emoji', 'lead_count', 'view_count', 'case_count', 'visa_regime')
            ->get()
            ->map(function ($c) use ($workCodes) {
                $c->agency_works = in_array($c->country_code, $workCodes);
                $c->interest = ($c->lead_count ?? 0) + ($c->view_count ?? 0);
                return $c;
            });

        $topPopular = $popularCountries->take(5)->values();
        $trendingPopular = $popularCountries->slice(5)->values();

        return [
            'top_countries'     => $topCountries,
            'lead_sources'      => $byLeadSource,
            'daily_trend'       => $dailyTrend,
            'by_priority'       => $byPriority,
            'popular_countries' => [
                'top'      => $topPopular,
                'trending' => $trendingPopular,
            ],
            'work_codes'        => $workCodes,
        ];
    }
}
