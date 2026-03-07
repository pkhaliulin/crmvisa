<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $today    = now()->toDateString();

        // Заявки по этапам
        $byStage = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->pluck('count', 'stage');

        // Горящие — дедлайн через <=5 дней
        $critical = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '>=', $today)
            ->whereDate('critical_date', '<=', now()->addDays(5)->toDateString())
            ->count();

        // Просроченные
        $overdue = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '<', $today)
            ->count();

        // Без ответственного
        $unassigned = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->whereNull('assigned_to')
            ->count();

        $totalActive = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])->count();

        // Нагрузка менеджеров
        $managerLoad = VisaCase::where('cases.agency_id', $agencyId)
            ->whereNotIn('cases.stage', ['result'])
            ->whereNotNull('assigned_to')
            ->join('users', 'users.id', '=', 'cases.assigned_to')
            ->select('users.id', 'users.name', DB::raw('count(cases.id) as active_cases'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('active_cases')
            ->get();

        // --- Расширенная аналитика ---

        // По источникам лидов (lead_source из cases + source из clients)
        $byLeadSource = VisaCase::where('cases.agency_id', $agencyId)
            ->leftJoin('clients', 'clients.id', '=', 'cases.client_id')
            ->selectRaw("COALESCE(cases.lead_source, clients.source, 'direct') as source, COUNT(*) as count")
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        // Динамика за 30 дней (по дням)
        $dailyTrend = VisaCase::where('agency_id', $agencyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, COUNT(*) as created, COUNT(CASE WHEN stage='result' THEN 1 END) as completed")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Ключевые метрики за 30 дней
        $period30 = now()->subDays(30);
        $newLeads = VisaCase::where('agency_id', $agencyId)
            ->where('created_at', '>=', $period30)->count();

        $completedTotal = VisaCase::where('agency_id', $agencyId)
            ->where('stage', 'result')->count();

        $completed30 = VisaCase::where('agency_id', $agencyId)
            ->where('stage', 'result')
            ->where('created_at', '>=', $period30)->count();

        $visaIssued = VisaCase::where('agency_id', $agencyId)
            ->where('stage', 'result')
            ->where('result_type', 'approved')
            ->where('created_at', '>=', $period30)->count();

        $totalAll = VisaCase::where('agency_id', $agencyId)->count();

        // Конверсии
        $conversionLeadToCase = $totalAll > 0
            ? round(($totalAll - VisaCase::where('agency_id', $agencyId)->where('stage', 'lead')->count()) / $totalAll * 100, 1)
            : 0;
        $conversionCaseToVisa = $totalAll > 0
            ? round($visaIssued / max(1, $completedTotal) * 100, 1)
            : 0;

        // Клиенты и сотрудники
        $totalClients = Client::where('agency_id', $agencyId)->count();
        $totalUsers = User::where('agency_id', $agencyId)->where('is_active', true)->count();

        // По странам (top-5)
        $topCountries = VisaCase::where('agency_id', $agencyId)
            ->select('country_code', DB::raw('COUNT(*) as total'))
            ->groupBy('country_code')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // По приоритетам
        $byPriority = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority');

        // Подсказки (hints)
        $hints = $this->generateHints($agencyId, $overdue, $critical, $unassigned, $totalActive, $managerLoad);

        return ApiResponse::success([
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
                'new_leads_30d'        => $newLeads,
                'completed_30d'        => $completed30,
                'visa_issued_30d'      => $visaIssued,
                'completed_total'      => $completedTotal,
                'conversion_lead_case' => $conversionLeadToCase,
                'conversion_case_visa' => $conversionCaseToVisa,
            ],
            'lead_sources'   => $byLeadSource,
            'daily_trend'    => $dailyTrend,
            'top_countries'  => $topCountries,
            'managers'       => $managerLoad,
            'clients_total'  => $totalClients,
            'users_total'    => $totalUsers,
            'hints'          => $hints,
        ]);
    }

    private function generateHints(string $agencyId, int $overdue, int $critical, int $unassigned, int $totalActive, $managerLoad): array
    {
        $hints = [];

        if ($overdue > 0) {
            $hints[] = [
                'type'    => 'warning',
                'title'   => 'Просроченные заявки',
                'message' => "У вас {$overdue} просроченных заявок. Каждый день просрочки снижает шансы клиента на визу и репутацию агентства. Проверьте раздел \"Просрочки\".",
                'action'  => '/app/overdue',
            ];
        }

        if ($unassigned > 0) {
            $hints[] = [
                'type'    => 'info',
                'title'   => 'Заявки без менеджера',
                'message' => "{$unassigned} заявок ожидают назначения менеджера. Быстрое назначение = быстрый старт обработки. Откройте Канбан и назначьте ответственных.",
                'action'  => '/app/kanban',
            ];
        }

        if ($critical > 3) {
            $hints[] = [
                'type'    => 'warning',
                'title'   => 'Много горящих заявок',
                'message' => "{$critical} заявок с дедлайном в ближайшие 5 дней. Рекомендуем распределить нагрузку между менеджерами равномерно.",
            ];
        }

        // Перегрузка менеджера
        $maxLoad = $managerLoad->max('active_cases');
        $minLoad = $managerLoad->min('active_cases');
        if ($managerLoad->count() > 1 && $maxLoad > 0 && $maxLoad > $minLoad * 3) {
            $busiest = $managerLoad->firstWhere('active_cases', $maxLoad);
            $hints[] = [
                'type'    => 'tip',
                'title'   => 'Неравномерная нагрузка',
                'message' => "{$busiest->name} ведёт {$maxLoad} заявок — это значительно больше, чем у коллег. Рассмотрите перераспределение для повышения скорости обработки.",
            ];
        }

        if ($totalActive === 0 && $overdue === 0) {
            $hints[] = [
                'type'    => 'success',
                'title'   => 'Всё под контролем',
                'message' => 'Нет активных заявок и просрочек. Отличное время для привлечения новых клиентов через маркетплейс или рекламу в соцсетях.',
            ];
        }

        return $hints;
    }

    public function overdue(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $cases = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
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
