<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\AgencyWorkCountry;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\Owner\Models\PortalCountry;
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

        // Исключаем неоплаченные заявки
        $excludeUnpaid = fn ($q) => $q->where(function ($sub) {
            $sub->whereNotIn('public_status', ['draft', 'awaiting_payment', 'cancelled'])
                ->orWhereNull('public_status');
        });

        // Scope по периоду (cases.created_at для JOIN)
        $inPeriod = fn ($q) => $dateFrom
            ? $q->whereBetween('cases.created_at', [$dateFrom, $dateTo])
            : $q;

        // SLA нормы из config
        $slaNorms = [];
        foreach (config('stages') as $key => $cfg) {
            $slaNorms[$key] = $cfg['sla_hours'] ?? null;
        }

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

        // === Менеджеры (расширенная аналитика) ===

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
                DB::raw("ROUND(AVG(CASE WHEN cases.stage = 'result' THEN EXTRACT(EPOCH FROM (cases.updated_at - cases.created_at)) / 3600 END), 1) as avg_hours"),
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('active_cases')
            ->get()
            ->map(function ($m) {
                $total = $m->active_cases + $m->completed_cases;
                $m->conversion = $total > 0
                    ? round($m->approved_cases / $total * 100, 1)
                    : 0;
                $m->avg_hours = (float) ($m->avg_hours ?? 0);
                return $m;
            });

        // === Источники лидов ===

        $byLeadSource = VisaCase::where('cases.agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where($inPeriod)
            ->leftJoin('clients', 'clients.id', '=', 'cases.client_id')
            ->selectRaw("COALESCE(cases.lead_source, clients.source, 'direct') as source, COUNT(*) as count")
            ->groupByRaw("COALESCE(cases.lead_source, clients.source, 'direct')")
            ->orderByDesc('count')
            ->get();

        // === Динамика за период ===

        $trendFrom = $dateFrom ?? now()->subDays(30)->toDateString();
        $dailyTrend = VisaCase::where('agency_id', $agencyId)
            ->where($excludeUnpaid)
            ->where('created_at', '>=', $trendFrom)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as created, COUNT(CASE WHEN stage='result' THEN 1 END) as completed")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // === Топ стран ===

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

        // === Аналитика по этапам (из case_stages) ===

        $stageAnalyticsRaw = DB::table('case_stages')
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
            ->keyBy('stage');

        // Формируем stage_analytics для ВСЕХ этапов, включая пустые
        $stageAnalytics = [];
        foreach (config('stages') as $key => $cfg) {
            $raw = $stageAnalyticsRaw[$key] ?? null;
            $slaHours = $cfg['sla_hours'] ?? null;
            $avgHours = $raw ? (float) $raw->avg_hours : null;
            $totalTransitions = $raw ? (int) $raw->total_transitions : 0;
            $overdueCount = $raw ? (int) $raw->overdue_count : 0;
            $slaCompliance = $totalTransitions > 0
                ? round(($totalTransitions - $overdueCount) / $totalTransitions * 100, 1)
                : 100;

            $stageAnalytics[$key] = [
                'stage'             => $key,
                'total_transitions' => $totalTransitions,
                'avg_hours'         => $avgHours,
                'max_hours'         => $raw ? (float) $raw->max_hours : null,
                'overdue_count'     => $overdueCount,
                'sla_compliance'    => $slaCompliance,
                'sla_norm_hours'    => $slaHours,
                'deviation'         => ($avgHours !== null && $slaHours !== null && $slaHours > 0)
                    ? round($avgHours - $slaHours, 1)
                    : null,
            ];
        }

        // === Среднее время обработки ===

        $avgProcessingHours = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->where('stage', 'result')
            ->whereNotNull('updated_at')
            ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 3600), 1) as avg_hours")
            ->value('avg_hours');

        // === Рост по периодам ===

        $growth = $this->calculateGrowth($agencyId, $dateFrom, $dateTo, $excludeUnpaid);

        // === Повторные клиенты ===

        $repeatClients = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->select('client_id')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        // === Процент без менеджера ===

        $unassignedPct = $totalActive > 0 ? round($unassigned / $totalActive * 100, 1) : 0;
        $overduePct = $totalActive > 0 ? round($overdue / $totalActive * 100, 1) : 0;

        // === Популярные страны платформы ===

        $workCodes = AgencyWorkCountry::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->pluck('country_code')
            ->toArray();

        $popularCountries = PortalCountry::where('is_active', true)
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

        // === Подсказки ===

        $hints = $this->generateHints(
            $agencyId, $overdue, $critical, $unassigned, $totalActive,
            $managerLoad, $stageAnalytics, $repeatClients, $totalClients,
            $byLeadSource, $avgProcessingHours, $unassignedPct, $overduePct,
            $topPopular, $trendingPopular, $workCodes
        );

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
                'unassigned_pct'       => $unassignedPct,
                'overdue_pct'          => $overduePct,
            ],
            'lead_sources'    => $byLeadSource,
            'daily_trend'     => $dailyTrend,
            'top_countries'   => $topCountries,
            'managers'        => $managerLoad,
            'stage_analytics' => $stageAnalytics,
            'sla_norms'       => $slaNorms,
            'clients_total'   => $totalClients,
            'users_total'     => $totalUsers,
            'repeat_clients'  => $repeatClients,
            'growth'          => $growth,
            'hints'           => $hints,
            'popular_countries' => [
                'top'      => $topPopular,
                'trending' => $trendingPopular,
            ],
        ]);
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

    private function generateHints(
        string $agencyId, int $overdue, int $critical, int $unassigned, int $totalActive,
        $managerLoad, array $stageAnalytics, int $repeatClients, int $totalClients,
        $byLeadSource, ?float $avgProcessingHours, float $unassignedPct, float $overduePct,
        $topPopular = null, $trendingPopular = null, array $workCodes = []
    ): array {
        $hints = [];

        if ($overdue > 0) {
            $hints[] = [
                'type'   => 'warning',
                'key'    => 'overdue',
                'params' => ['n' => $overdue],
                'action' => '/app/cases?status=overdue',
            ];
        }

        if ($unassigned > 0) {
            $hints[] = [
                'type'   => 'info',
                'key'    => 'unassigned',
                'params' => ['n' => $unassigned, 'pct' => $unassignedPct],
                'action' => '/app/cases?assigned_to=unassigned',
            ];
        }

        if ($critical > 3) {
            $hints[] = [
                'type'   => 'warning',
                'key'    => 'critical',
                'params' => ['n' => $critical],
                'action' => '/app/cases?status=critical',
            ];
        }

        // Перегрузка менеджера
        $maxLoad = $managerLoad->max('active_cases');
        $minLoad = $managerLoad->min('active_cases');
        if ($managerLoad->count() > 1 && $maxLoad > 0 && $maxLoad > $minLoad * 3) {
            $busiest = $managerLoad->firstWhere('active_cases', $maxLoad);
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'imbalance',
                'params' => ['name' => $busiest->name, 'n' => $maxLoad],
            ];
        }

        // Низкий SLA compliance
        foreach ($stageAnalytics as $stage => $data) {
            if ($data['sla_compliance'] < 70 && $data['total_transitions'] >= 5) {
                $hints[] = [
                    'type'   => 'warning',
                    'key'    => 'lowSla',
                    'params' => ['stage' => $stage, 'pct' => $data['sla_compliance']],
                ];
                break;
            }
        }

        // Высокое среднее время
        if ($avgProcessingHours && $avgProcessingHours > 240) { // > 10 дней
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'slowProcessing',
                'params' => ['days' => round($avgProcessingHours / 24, 1)],
            ];
        }

        // Низкая доля внешних каналов
        $totalLeads = $byLeadSource->sum('count');
        $directCount = $byLeadSource->firstWhere('source', 'direct')?->count ?? 0;
        if ($totalLeads > 10 && $directCount / $totalLeads > 0.7) {
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'lowExternalLeads',
                'params' => ['pct' => round($directCount / $totalLeads * 100)],
            ];
        }

        // Мало повторных клиентов
        if ($totalClients >= 10 && $repeatClients === 0) {
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'noRepeatClients',
                'params' => [],
            ];
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

        // Высокая просрочка
        if ($overduePct > 20 && $overdue > 2) {
            $hints[] = [
                'type'   => 'warning',
                'key'    => 'highOverdueRate',
                'params' => ['pct' => $overduePct],
            ];
        }

        // Этапы, где скапливаются заявки
        if ($totalActive > 5) {
            foreach ($stageAnalytics as $stage => $data) {
                if ($data['avg_hours'] !== null && $data['sla_norm_hours'] !== null && $data['deviation'] !== null && $data['deviation'] > $data['sla_norm_hours'] * 0.5) {
                    $hints[] = [
                        'type'   => 'warning',
                        'key'    => 'stageBottleneck',
                        'params' => ['stage' => $stage, 'deviation' => round($data['deviation'], 1)],
                    ];
                    break;
                }
            }
        }

        // Популярные страны, с которыми агентство не работает
        if ($topPopular) {
            $missedCountries = $topPopular->filter(fn ($c) => !$c->agency_works)->take(3);
            if ($missedCountries->count() > 0) {
                $names = $missedCountries->pluck('name')->implode(', ');
                $hints[] = [
                    'type'   => 'tip',
                    'key'    => 'missedCountries',
                    'params' => ['countries' => $names, 'n' => $missedCountries->count()],
                    'action' => '/app/countries',
                ];
            }
        }

        // Слабое покрытие популярных направлений
        if ($topPopular && $topPopular->count() >= 5) {
            $covered = $topPopular->filter(fn ($c) => $c->agency_works)->count();
            if ($covered < 2) {
                $hints[] = [
                    'type'   => 'warning',
                    'key'    => 'lowCoverage',
                    'params' => ['covered' => $covered, 'total' => $topPopular->count()],
                    'action' => '/app/countries',
                ];
            }
        }

        // Один канал доминирует — риск
        if ($totalLeads > 10) {
            $topSource = $byLeadSource->first();
            if ($topSource && $topSource->count / $totalLeads > 0.6) {
                $hints[] = [
                    'type'   => 'tip',
                    'key'    => 'singleChannel',
                    'params' => ['source' => $topSource->source, 'pct' => round($topSource->count / $totalLeads * 100)],
                    'action' => '/app/leadgen',
                ];
            }
        }

        // Instagram высокий интерес, низкая конверсия
        $igLeads = $byLeadSource->firstWhere('source', 'instagram');
        if ($igLeads && $totalLeads > 10 && $igLeads->count / $totalLeads > 0.15) {
            $igCases = VisaCase::where('agency_id', $agencyId)->where('lead_source', 'instagram')->where('stage', 'result')->count();
            $igConversion = $igLeads->count > 0 ? round($igCases / $igLeads->count * 100) : 0;
            if ($igConversion < 30) {
                $hints[] = [
                    'type'   => 'tip',
                    'key'    => 'igLowConversion',
                    'params' => ['pct' => $igConversion],
                    'action' => '/app/leadgen',
                ];
            }
        }

        // Telegram качественные заявки
        $tgLeads = $byLeadSource->firstWhere('source', 'telegram');
        if ($tgLeads && $totalLeads > 5 && $tgLeads->count >= 3) {
            $tgCases = VisaCase::where('agency_id', $agencyId)->where('lead_source', 'telegram')->where('stage', 'result')->where('result_type', 'approved')->count();
            $tgConversion = $tgLeads->count > 0 ? round($tgCases / $tgLeads->count * 100) : 0;
            if ($tgConversion > 50) {
                $hints[] = [
                    'type'   => 'success',
                    'key'    => 'tgHighConversion',
                    'params' => ['pct' => $tgConversion],
                    'action' => '/app/leadgen',
                ];
            }
        }

        // Мало рекомендаций
        $referralCount = $byLeadSource->firstWhere('source', 'referral')?->count ?? 0;
        if ($totalLeads > 10 && $referralCount / $totalLeads < 0.05) {
            $hints[] = [
                'type'   => 'tip',
                'key'    => 'lowReferrals',
                'params' => [],
                'action' => '/app/leadgen',
            ];
        }

        // Все OK
        if ($totalActive === 0 && $overdue === 0) {
            $hints[] = [
                'type'   => 'success',
                'key'    => 'allClear',
                'params' => [],
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
