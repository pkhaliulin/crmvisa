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
            ->select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->pluck('count', 'stage');

        // Горящие — дедлайн через ≤5 дней
        $critical = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '>=', $today)
            ->whereDate('critical_date', '<=', now()->addDays(5)->toDateString())
            ->count();

        // Просроченные — дедлайн уже прошёл
        $overdue = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '<', $today)
            ->count();

        // Нагрузка менеджеров
        $managerLoad = VisaCase::where('cases.agency_id', $agencyId)
            ->whereNotIn('cases.stage', ['result'])
            ->whereNotNull('assigned_to')
            ->join('users', 'users.id', '=', 'cases.assigned_to')
            ->select('users.id', 'users.name', DB::raw('count(cases.id) as active_cases'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('active_cases')
            ->get();

        // Без ответственного
        $unassigned = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->whereNull('assigned_to')
            ->count();

        // Статистика за последние 30 дней
        $recentStats = VisaCase::where('agency_id', $agencyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("
                count(*) as total_created,
                count(case when stage = 'result' then 1 end) as completed
            ")
            ->first();

        // Всего клиентов
        $totalClients = Client::where('agency_id', $agencyId)->count();

        // Всего сотрудников
        $totalUsers = User::where('agency_id', $agencyId)->where('is_active', true)->count();

        return ApiResponse::success([
            'cases' => [
                'by_stage'    => $byStage,
                'critical'    => $critical,
                'overdue'     => $overdue,
                'unassigned'  => $unassigned,
                'total_active'=> VisaCase::where('agency_id', $agencyId)
                    ->whereNotIn('stage', ['result'])->count(),
            ],
            'managers'     => $managerLoad,
            'clients_total'=> $totalClients,
            'users_total'  => $totalUsers,
            'last_30_days' => $recentStats,
        ]);
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

        // Убедимся что менеджер из того же агентства
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
