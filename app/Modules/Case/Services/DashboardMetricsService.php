<?php

namespace App\Modules\Case\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    public function calculate(string $agencyId, ?string $dateFrom, ?string $dateTo): array
    {
        $today = now()->toDateString();

        $excludeUnpaid = $this->excludeUnpaidScope();

        $inPeriod = fn ($q) => $dateFrom
            ? $q->whereBetween('cases.created_at', [$dateFrom, $dateTo])
            : $q;

        // === Текущие показатели (без периода) ===

        $byStage = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->pluck('count', 'stage');

        $critical = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '>=', $today)
            ->whereDate('critical_date', '<=', now()->addDays(5)->toDateString())
            ->count();

        $overdue = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '<', $today)
            ->count();

        $unassigned = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->whereNull('assigned_to')
            ->count();

        $totalActive = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)->count();

        // === Метрики по периоду ===

        $newLeads = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where($inPeriod)
            ->count();

        $completedTotal = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('stage', 'result')->count();

        $completedPeriod = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('stage', 'result')
            ->where($inPeriod)->count();

        $visaIssued = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('stage', 'result')
            ->where('result_type', 'approved')
            ->where($inPeriod)->count();

        $totalAll = VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)->count();

        $conversionLeadToCase = $totalAll > 0
            ? round(($totalAll - VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)->where('stage', 'lead')->count()) / $totalAll * 100, 1)
            : 0;
        $conversionCaseToVisa = $completedTotal > 0
            ? round($visaIssued / $completedTotal * 100, 1)
            : 0;

        // Клиенты и сотрудники
        $totalClients = Client::where('agency_id', $agencyId)->count();
        $totalUsers = User::where('agency_id', $agencyId)->where('is_active', true)->count();

        // Повторные клиенты
        $repeatClients = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->select('client_id')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // Процент без менеджера / просроченных
        $unassignedPct = $totalActive > 0 ? round($unassigned / $totalActive * 100, 1) : 0;
        $overduePct = $totalActive > 0 ? round($overdue / $totalActive * 100, 1) : 0;

        // Рост по периодам
        $growth = $this->calculateGrowth($agencyId, $dateFrom, $dateTo, $excludeUnpaid);

        return [
            'cases' => [
                'by_stage'     => $byStage,
                'critical'     => $critical,
                'overdue'      => $overdue,
                'unassigned'   => $unassigned,
                'total_active' => $totalActive,
                'total_all'    => $totalAll,
            ],
            'metrics' => [
                'new_leads'            => $newLeads,
                'completed'            => $completedPeriod,
                'visa_issued'          => $visaIssued,
                'completed_total'      => $completedTotal,
                'conversion_lead_case' => $conversionLeadToCase,
                'conversion_case_visa' => $conversionCaseToVisa,
                'unassigned_pct'       => $unassignedPct,
                'overdue_pct'          => $overduePct,
            ],
            'growth'         => $growth,
            'clients_total'  => $totalClients,
            'users_total'    => $totalUsers,
            'repeat_clients' => $repeatClients,
        ];
    }

    private function calculateGrowth(string $agencyId, ?string $dateFrom, ?string $dateTo, \Closure $excludeUnpaid): array
    {
        if (!$dateFrom) {
            return ['new_leads' => 0, 'completed' => 0, 'visa_issued' => 0];
        }

        $days = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
        $prevFrom = Carbon::parse($dateFrom)->subDays($days + 1)->toDateString();
        $prevTo   = Carbon::parse($dateFrom)->subDay()->toDateString();

        $pct = function ($cur, $prev) {
            return $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : ($cur > 0 ? 100 : 0);
        };

        $curLeads = VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)
            ->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $prevLeads = VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)
            ->whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $curCompleted = VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)
            ->where('stage', 'result')->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $prevCompleted = VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)
            ->where('stage', 'result')->whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $curVisa = VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)
            ->where('stage', 'result')->where('result_type', 'approved')
            ->whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $prevVisa = VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)
            ->where('stage', 'result')->where('result_type', 'approved')
            ->whereBetween('created_at', [$prevFrom, $prevTo])->count();

        return [
            'new_leads'   => $pct($curLeads, $prevLeads),
            'completed'   => $pct($curCompleted, $prevCompleted),
            'visa_issued' => $pct($curVisa, $prevVisa),
        ];
    }

    public static function excludeUnpaidScope(): \Closure
    {
        return fn ($q) => $q->where(function ($sub) {
            $sub->whereNotIn('public_status', ['draft', 'awaiting_payment', 'cancelled'])
                ->orWhereNull('public_status');
        });
    }
}
