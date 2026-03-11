<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Requests\SaveGoalRequest;
use App\Modules\Case\Services\DashboardCountryService;
use App\Modules\Case\Services\DashboardHintsService;
use App\Modules\Case\Services\DashboardManagerService;
use App\Modules\Case\Services\DashboardMetricsService;
use App\Modules\Case\Services\DashboardStageService;
use App\Modules\Client\Models\Client;
use App\Modules\Agency\Models\AgencyGoal;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use App\Support\Services\AgencyCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardMetricsService $metricsService,
        private DashboardManagerService $managerService,
        private DashboardStageService $stageService,
        private DashboardCountryService $countryService,
        private DashboardHintsService $hintsService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        [$dateFrom, $dateTo, $periodKey] = $this->resolvePeriod($request);

        $data = AgencyCacheService::getDashboard($agencyId, $periodKey, function () use ($agencyId, $dateFrom, $dateTo, $periodKey) {
            return $this->buildDashboardData($agencyId, $dateFrom, $dateTo, $periodKey);
        });

        return ApiResponse::success($data);
    }

    private function buildDashboardData(string $agencyId, ?string $dateFrom, ?string $dateTo, string $periodKey): array
    {
        $metrics = $this->metricsService->calculate($agencyId, $dateFrom, $dateTo);
        $managers = $this->managerService->getManagerAnalytics($agencyId);
        $stages = $this->stageService->getStageAnalytics($agencyId);
        $countries = $this->countryService->getCountryData($agencyId, $dateFrom, $dateTo);

        $hints = $this->hintsService->generate($agencyId, [
            'overdue'             => $metrics['cases']['overdue'],
            'critical'            => $metrics['cases']['critical'],
            'unassigned'          => $metrics['cases']['unassigned'],
            'totalActive'         => $metrics['cases']['total_active'],
            'managerLoad'         => $managers['managers'],
            'stageAnalytics'      => $stages['stage_analytics'],
            'repeatClients'       => $metrics['repeat_clients'],
            'totalClients'        => $metrics['clients_total'],
            'byLeadSource'        => $countries['lead_sources'],
            'avgProcessingHours'  => $stages['avg_processing_hours'],
            'unassignedPct'       => $metrics['metrics']['unassigned_pct'],
            'overduePct'          => $metrics['metrics']['overdue_pct'],
            'topPopular'          => $countries['popular_countries']['top'],
            'trendingPopular'     => $countries['popular_countries']['trending'],
            'workCodes'           => $countries['work_codes'],
        ]);

        return [
            'period' => $periodKey,
            'cases' => array_merge($metrics['cases'], [
                'by_priority' => $countries['by_priority'],
            ]),
            'metrics' => array_merge($metrics['metrics'], [
                'avg_processing_hours' => $stages['avg_processing_hours'],
            ]),
            'lead_sources'      => $countries['lead_sources'],
            'daily_trend'       => $countries['daily_trend'],
            'top_countries'     => $countries['top_countries'],
            'managers'          => $managers['managers'],
            'stage_analytics'   => $stages['stage_analytics'],
            'sla_norms'         => $stages['sla_norms'],
            'clients_total'     => $metrics['clients_total'],
            'users_total'       => $metrics['users_total'],
            'managers_count'    => $managers['managers_count'],
            'repeat_clients'    => $metrics['repeat_clients'],
            'growth'            => $metrics['growth'],
            'hints'             => $hints,
            'popular_countries' => $countries['popular_countries'],
        ];
    }

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

        // Год: 2023, 2024, 2025, 2026...
        if (preg_match('/^\d{4}$/', $p)) {
            return ["{$p}-01-01", "{$p}-12-31", $p];
        }

        return [now()->subDays(30)->toDateString(), now()->toDateString(), '30d'];
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

    public function goals(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $year = (int) $request->input('year', now()->year);

        $goals = AgencyGoal::where('agency_id', $agencyId)
            ->where('year', $year)
            ->orderBy('month')
            ->get();

        // Текущий прогресс за год
        $yearStart = "{$year}-01-01";
        $yearEnd   = "{$year}-12-31";

        $actualClients = Client::where('agency_id', $agencyId)
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->count();

        $actualCases = VisaCase::where('agency_id', $agencyId)
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->count();

        $actualRevenue = (float) DB::table('client_payments')
            ->where('agency_id', $agencyId)
            ->where('status', 'succeeded')
            ->whereBetween('created_at', [$yearStart, $yearEnd])
            ->sum('amount');

        return ApiResponse::success([
            'year'     => $year,
            'goals'    => $goals,
            'progress' => [
                'clients' => $actualClients,
                'cases'   => $actualCases,
                'revenue' => $actualRevenue,
            ],
        ]);
    }

    public function saveGoal(SaveGoalRequest $request): JsonResponse
    {
        if (! in_array($request->user()->role, ['owner', 'superadmin'])) {
            abort(403, 'Only owner can manage goals.');
        }

        $agencyId = $request->user()->agency_id;

        $goal = AgencyGoal::updateOrCreate(
            [
                'agency_id' => $agencyId,
                'year'      => $request->input('year'),
                'month'     => $request->input('month'),
            ],
            [
                'target_clients' => $request->input('target_clients'),
                'target_revenue' => $request->input('target_revenue'),
                'target_cases'   => $request->input('target_cases'),
                'created_by'     => $request->user()->id,
            ]
        );

        return ApiResponse::success($goal);
    }

    public function activityFeed(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        // Пробуем activity_log (spatie/activitylog)
        $hasActivity = DB::table('activity_log')
            ->whereIn('subject_type', [
                'App\\Modules\\Case\\Models\\VisaCase',
                'App\\Modules\\Client\\Models\\Client',
            ])
            ->exists();

        if ($hasActivity) {
            $events = DB::table('activity_log')
                ->leftJoin('cases', function ($join) {
                    $join->whereRaw("activity_log.subject_id::uuid = cases.id")
                         ->where('activity_log.subject_type', 'App\\Modules\\Case\\Models\\VisaCase');
                })
                ->leftJoin('clients', function ($join) {
                    $join->whereRaw("activity_log.subject_id::uuid = clients.id")
                         ->where('activity_log.subject_type', 'App\\Modules\\Client\\Models\\Client');
                })
                ->leftJoin('users as causer', DB::raw('activity_log.causer_id::uuid'), '=', 'causer.id')
                ->where(function ($q) use ($agencyId) {
                    $q->where('cases.agency_id', $agencyId)
                      ->orWhere('clients.agency_id', $agencyId);
                })
                ->select(
                    DB::raw("'activity' as type"),
                    'activity_log.description',
                    'causer.name as user_name',
                    'activity_log.created_at',
                    'cases.id as case_id',
                    'cases.case_number',
                )
                ->orderByDesc('activity_log.created_at')
                ->limit(20)
                ->get();

            return ApiResponse::success($events);
        }

        // Фолбэк: case_stages
        $events = DB::table('case_stages')
            ->join('cases', 'cases.id', '=', 'case_stages.case_id')
            ->leftJoin('users', 'users.id', '=', 'case_stages.entered_by')
            ->where('cases.agency_id', $agencyId)
            ->select(
                DB::raw("'stage_change' as type"),
                DB::raw("CONCAT('Stage: ', case_stages.stage) as description"),
                'users.name as user_name',
                'case_stages.entered_at as created_at',
                'cases.id as case_id',
                'cases.case_number',
            )
            ->orderByDesc('case_stages.entered_at')
            ->limit(20)
            ->get();

        return ApiResponse::success($events);
    }

    public function financialSummary(Request $request): JsonResponse
    {
        if (! in_array($request->user()->role, ['owner', 'superadmin'])) {
            abort(403, 'Only owner can view financial data.');
        }

        $agencyId = $request->user()->agency_id;

        [$dateFrom, $dateTo, $periodKey] = $this->resolvePeriod($request);

        $data = AgencyCacheService::getFinancial($agencyId, $periodKey, function () use ($agencyId, $dateFrom, $dateTo, $periodKey) {
            return $this->buildFinancialData($agencyId, $dateFrom, $dateTo, $periodKey);
        });

        return ApiResponse::success($data);
    }

    private function buildFinancialData(string $agencyId, ?string $dateFrom, ?string $dateTo, string $periodKey): array
    {
        $totalRevenue = (float) DB::table('client_payments')
            ->where('agency_id', $agencyId)
            ->where('status', 'succeeded')
            ->sum('amount');

        $periodRevenueQuery = DB::table('client_payments')
            ->where('agency_id', $agencyId)
            ->where('status', 'succeeded');

        if ($dateFrom) {
            $periodRevenueQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $periodRevenue = (float) $periodRevenueQuery->sum('amount');

        $avgCheck = (float) DB::table('client_payments')
            ->where('agency_id', $agencyId)
            ->where('status', 'succeeded')
            ->avg('amount');

        $pendingPayments = (float) DB::table('client_payments')
            ->where('agency_id', $agencyId)
            ->where('status', 'pending')
            ->sum('amount');

        $paymentCountQuery = DB::table('client_payments')
            ->where('agency_id', $agencyId)
            ->where('status', 'succeeded');

        if ($dateFrom) {
            $paymentCountQuery->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $paymentCount = $paymentCountQuery->count();

        $avgPackagePrice = (float) DB::table('agency_service_packages')
            ->where('agency_id', $agencyId)
            ->where('is_active', true)
            ->avg('price');

        return [
            'period'            => $periodKey,
            'total_revenue'     => round($totalRevenue, 2),
            'period_revenue'    => round($periodRevenue, 2),
            'avg_check'         => round($avgCheck, 2),
            'pending_payments'  => round($pendingPayments, 2),
            'payment_count'     => $paymentCount,
            'avg_package_price' => round($avgPackagePrice, 2),
        ];
    }
}
