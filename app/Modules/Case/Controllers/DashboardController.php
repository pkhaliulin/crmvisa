<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $today    = now()->toDateString();

        [$dateFrom, $dateTo, $periodKey] = $this->resolvePeriod($request);

        // Исключаем неоплаченные заявки из всей статистики агентства
        $excludeUnpaid = fn ($q) => $q->where(function ($sub) {
            $sub->whereNotIn('public_status', ['draft', 'awaiting_payment', 'cancelled'])
                ->orWhereNull('public_status');
        });

        // Scope по периоду (cases.created_at для избежания ambiguous в JOIN)
        $inPeriod = fn ($q) => $dateFrom
            ? $q->whereBetween('cases.created_at', [$dateFrom, $dateTo])
            : $q;

        // Заявки по этапам (всегда текущие, без периода)
        $byStage = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->pluck('count', 'stage');

        // Горящие — дедлайн через <=5 дней
        $critical = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '>=', $today)
            ->whereDate('critical_date', '<=', now()->addDays(5)->toDateString())
            ->count();

        // Просроченные
        $overdue = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '<', $today)
            ->count();

        // Без ответственного
        $unassigned = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->whereNull('assigned_to')
            ->count();

        $totalActive = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)->count();

        // Нагрузка менеджеров (расширенная)
        $managerLoad = VisaCase::where('cases.agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->whereNotNull('assigned_to')
            ->join('users', 'users.id', '=', 'cases.assigned_to')
            ->select(
                'users.id',
                'users.name',
                DB::raw("COUNT(CASE WHEN cases.stage != 'result' THEN 1 END) as active_cases"),
                DB::raw("COUNT(CASE WHEN cases.stage = 'result' THEN 1 END) as completed_cases"),
                DB::raw("COUNT(CASE WHEN cases.stage = 'result' AND cases.result_type = 'approved' THEN 1 END) as approved_cases"),
                DB::raw("COUNT(CASE WHEN cases.critical_date IS NOT NULL AND cases.critical_date < CURRENT_DATE AND cases.stage != 'result' THEN 1 END) as overdue_cases"),
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('active_cases')
            ->get()
            ->map(function ($m) {
                $total = $m->active_cases + $m->completed_cases;
                $m->conversion = $total > 0
                    ? round($m->approved_cases / $total * 100, 1)
                    : 0;
                return $m;
            });

        // --- Метрики по периоду ---

        // По источникам лидов
        $byLeadSource = VisaCase::where('cases.agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where($inPeriod)
            ->leftJoin('clients', 'clients.id', '=', 'cases.client_id')
            ->selectRaw("COALESCE(cases.lead_source, clients.source, 'direct') as source, COUNT(*) as count")
            ->groupByRaw("COALESCE(cases.lead_source, clients.source, 'direct')")
            ->orderByDesc('count')
            ->get();

        // Динамика за период (по дням)
        $trendDays = $dateFrom ? Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) : 30;
        $trendFrom = $dateFrom ?? now()->subDays(30)->toDateString();
        $dailyTrend = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('created_at', '>=', $trendFrom)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as created, COUNT(CASE WHEN stage='result' THEN 1 END) as completed")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ключевые метрики за период
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

        // Конверсии
        $conversionLeadToCase = $totalAll > 0
            ? round(($totalAll - VisaCase::where('agency_id', $agencyId)->where($excludeUnpaid)->where('stage', 'lead')->count()) / $totalAll * 100, 1)
            : 0;
        $conversionCaseToVisa = $completedTotal > 0
            ? round($visaIssued / $completedTotal * 100, 1)
            : 0;

        // Клиенты и сотрудники
        $totalClients = Client::where('agency_id', $agencyId)->count();
        $totalUsers = User::where('agency_id', $agencyId)->where('is_active', true)->count();

        // По странам (top-5)
        $topCountries = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->select('country_code', DB::raw('COUNT(*) as total'))
            ->groupBy('country_code')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // По приоритетам
        $byPriority = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->where($excludeUnpaid)
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority');

        // --- Аналитика по этапам (из case_stages) ---
        $stageAnalytics = DB::table('case_stages')
            ->join('cases', 'cases.id', '=', 'case_stages.case_id')
            ->where('cases.agency_id', $agencyId)
            ->whereNotNull('case_stages.exited_at')
            ->select(
                'case_stages.stage',
                DB::raw('COUNT(*) as total_transitions'),
                DB::raw("ROUND(AVG(EXTRACT(EPOCH FROM (case_stages.exited_at - case_stages.entered_at)) / 3600), 1) as avg_hours"),
                DB::raw("ROUND(MAX(EXTRACT(EPOCH FROM (case_stages.exited_at - case_stages.entered_at)) / 3600), 1) as max_hours"),
                DB::raw("COUNT(CASE WHEN case_stages.is_overdue THEN 1 END) as overdue_count"),
            )
            ->groupBy('case_stages.stage')
            ->get()
            ->map(function ($row) {
                $row->sla_compliance = $row->total_transitions > 0
                    ? round(($row->total_transitions - $row->overdue_count) / $row->total_transitions * 100, 1)
                    : 100;
                return $row;
            })
            ->keyBy('stage');

        // --- Среднее время обработки заявки (от lead до result) ---
        $avgProcessingHours = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->where('stage', 'result')
            ->whereNotNull('updated_at')
            ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 3600), 1) as avg_hours")
            ->value('avg_hours');

        // --- Рост: сравнение текущего и предыдущего периодов ---
        $growth = $this->calculateGrowth($agencyId, $dateFrom, $dateTo, $excludeUnpaid);

        // --- Повторные клиенты ---
        $repeatClients = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->select('client_id')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // Подсказки (hints)
        $hints = $this->generateHints($agencyId, $overdue, $critical, $unassigned, $totalActive, $managerLoad, $stageAnalytics, $repeatClients, $totalClients);

        return ApiResponse::success([
            'period' => $periodKey,
            'cases' => [
                'by_stage'     => $byStage,
                'by_priority'  => $byPriority,
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
                'avg_processing_hours' => (float) ($avgProcessingHours ?? 0),
            ],
            'lead_sources'    => $byLeadSource,
            'daily_trend'     => $dailyTrend,
            'top_countries'   => $topCountries,
            'managers'        => $managerLoad,
            'stage_analytics' => $stageAnalytics,
            'clients_total'   => $totalClients,
            'users_total'     => $totalUsers,
            'repeat_clients'  => $repeatClients,
            'growth'          => $growth,
            'hints'           => $hints,
        ]);
    }

    /**
     * Разбор параметра period -> [date_from, date_to, key]
     */
    private function resolvePeriod(Request $request): array
    {
        $p = $request->input('period', '30d');

        if ($p === 'all') {
            return [null, now()->toDateString(), 'all'];
        }

        // Числовые дни: 1d, 3d, 7d, 30d, 60d, 90d, 365d
        if (preg_match('/^(\d+)d$/', $p, $m)) {
            $days = (int) $m[1];
            return [now()->subDays($days)->toDateString(), now()->toDateString(), $p];
        }

        // Год: 2025, 2026
        if (preg_match('/^\d{4}$/', $p)) {
            return ["{$p}-01-01", "{$p}-12-31", $p];
        }

        // По умолчанию — 30 дней
        return [now()->subDays(30)->toDateString(), now()->toDateString(), '30d'];
    }

    /**
     * Рост: сравнение текущего периода с предыдущим аналогичным
     */
    private function calculateGrowth(string $agencyId, ?string $dateFrom, ?string $dateTo, \Closure $excludeUnpaid): array
    {
        if (!$dateFrom) {
            return ['new_leads' => 0, 'completed' => 0, 'visa_issued' => 0];
        }

        $days = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
        $prevFrom = Carbon::parse($dateFrom)->subDays($days + 1)->toDateString();
        $prevTo   = Carbon::parse($dateFrom)->subDay()->toDateString();

        $prevLeads = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->count();

        $prevCompleted = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('stage', 'result')
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->count();

        $prevVisa = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('stage', 'result')
            ->where('result_type', 'approved')
            ->whereBetween('created_at', [$prevFrom, $prevTo])
            ->count();

        $curLeads = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        $curCompleted = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('stage', 'result')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        $curVisa = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('stage', 'result')
            ->where('result_type', 'approved')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        $pct = fn ($cur, $prev) => $prev > 0 ? round(($cur - $prev) / $prev * 100, 1) : ($cur > 0 ? 100 : 0);

        return [
            'new_leads'  => $pct($curLeads, $prevLeads),
            'completed'  => $pct($curCompleted, $prevCompleted),
            'visa_issued' => $pct($curVisa, $prevVisa),
        ];
    }

    private function generateHints(string $agencyId, int $overdue, int $critical, int $unassigned, int $totalActive, $managerLoad, $stageAnalytics = null, int $repeatClients = 0, int $totalClients = 0): array
    {
        $hints = [];

        if ($overdue > 0) {
            $hints[] = [
                'type'    => 'warning',
                'key'     => 'overdue',
                'params'  => ['n' => $overdue],
                'action'  => '/app/overdue',
            ];
        }

        if ($unassigned > 0) {
            $hints[] = [
                'type'    => 'info',
                'key'     => 'unassigned',
                'params'  => ['n' => $unassigned],
                'action'  => '/app/kanban',
            ];
        }

        if ($critical > 3) {
            $hints[] = [
                'type'    => 'warning',
                'key'     => 'critical',
                'params'  => ['n' => $critical],
            ];
        }

        // Перегрузка менеджера
        $maxLoad = $managerLoad->max('active_cases');
        $minLoad = $managerLoad->min('active_cases');
        if ($managerLoad->count() > 1 && $maxLoad > 0 && $maxLoad > $minLoad * 3) {
            $busiest = $managerLoad->firstWhere('active_cases', $maxLoad);
            $hints[] = [
                'type'    => 'tip',
                'key'     => 'imbalance',
                'params'  => ['name' => $busiest->name, 'n' => $maxLoad],
            ];
        }

        // Низкий SLA compliance
        if ($stageAnalytics) {
            foreach ($stageAnalytics as $stage => $data) {
                if ($data->sla_compliance < 70 && $data->total_transitions >= 5) {
                    $hints[] = [
                        'type'   => 'warning',
                        'key'    => 'lowSla',
                        'params' => ['stage' => $stage, 'pct' => $data->sla_compliance],
                    ];
                    break; // Одна подсказка о SLA
                }
            }
        }

        // Повторные клиенты (позитив)
        if ($totalClients > 0 && $repeatClients > 0) {
            $pct = round($repeatClients / $totalClients * 100, 1);
            if ($pct >= 10) {
                $hints[] = [
                    'type'   => 'success',
                    'key'    => 'repeatClients',
                    'params' => ['n' => $repeatClients, 'pct' => $pct],
                ];
            }
        }

        if ($totalActive === 0 && $overdue === 0) {
            $hints[] = [
                'type'    => 'success',
                'key'     => 'allClear',
                'params'  => [],
            ];
        }

        return $hints;
    }

    public function overdue(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $cases = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->whereNotIn('public_status', ['draft', 'awaiting_payment', 'cancelled'])
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '<', now()->toDateString())
            ->with(['client:id,name,phone', 'assignee:id,name'])
            ->orderBy('critical_date')
            ->get()
            ->map(function ($case) {
                $case->days_overdue = now()->diffInDays($case->critical_date);
                return $case;
            });

        return ApiResponse::success($cases);
    }

    public function managerCases(Request $request, string $userId): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $manager = User::where('id', $userId)
            ->where('agency_id', $agencyId)
            ->firstOrFail();

        $cases = VisaCase::where('agency_id', $agencyId)
            ->where('assigned_to', $userId)
            ->whereNotIn('stage', ['result'])
            ->whereNotIn('public_status', ['draft', 'awaiting_payment', 'cancelled'])
            ->with(['client:id,name,phone'])
            ->orderByRaw("CASE WHEN critical_date IS NULL THEN 1 ELSE 0 END, critical_date ASC")
            ->get();

        return ApiResponse::success([
            'manager'      => ['id' => $manager->id, 'name' => $manager->name],
            'active_cases' => $cases,
            'total'        => $cases->count(),
        ]);
    }
}
