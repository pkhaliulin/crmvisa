<?php

namespace App\Modules\Agency\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function agencyId(Request $request): string
    {
        return $request->user()->agency_id;
    }

    public function overview(Request $request): JsonResponse
    {
        $agencyId = $this->agencyId($request);
        $from = $request->date('from', 'Y-m-d') ?? now()->subDays(30);
        $to   = $request->date('to', 'Y-m-d') ?? now();

        $total = VisaCase::where('agency_id', $agencyId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $completed = VisaCase::where('agency_id', $agencyId)
            ->where('stage', 'result')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $bySource = VisaCase::where('cases.agency_id', $agencyId)
            ->join('clients', 'clients.id', '=', 'cases.client_id')
            ->whereBetween('cases.created_at', [$from, $to])
            ->selectRaw('COALESCE(clients.source, \'direct\') as source, COUNT(*) as count')
            ->groupBy('source')
            ->get();

        $byMonth = VisaCase::where('agency_id', $agencyId)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as month, COUNT(*) as total, COUNT(CASE WHEN stage='result' THEN 1 END) as completed")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return ApiResponse::success([
            'total'       => $total,
            'completed'   => $completed,
            'conversion'  => $total > 0 ? round($completed / $total * 100, 1) : 0,
            'by_source'   => $bySource,
            'by_month'    => $byMonth,
        ]);
    }

    public function managers(Request $request): JsonResponse
    {
        $agencyId = $this->agencyId($request);

        $managers = User::where('agency_id', $agencyId)
            ->whereIn('role', ['manager', 'owner'])
            ->withCount([
                'cases as total_cases',
                'cases as active_cases' => fn ($q) => $q->whereNotIn('stage', ['result']),
                'cases as completed_cases' => fn ($q) => $q->where('stage', 'result'),
            ])
            ->get()
            ->map(function (User $user) {
                $overdue = VisaCase::where('assigned_to', $user->id)
                    ->whereNotIn('stage', ['result'])
                    ->whereNotNull('critical_date')
                    ->where('critical_date', '<', now())
                    ->count();

                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'total_cases'     => $user->total_cases,
                    'active_cases'    => $user->active_cases,
                    'completed_cases' => $user->completed_cases,
                    'overdue_cases'   => $overdue,
                    'conversion'      => $user->total_cases > 0
                        ? round($user->completed_cases / $user->total_cases * 100, 1)
                        : 0,
                ];
            });

        return ApiResponse::success($managers);
    }

    public function countries(Request $request): JsonResponse
    {
        $agencyId = $this->agencyId($request);

        $data = VisaCase::where('agency_id', $agencyId)
            ->selectRaw('country_code, COUNT(*) as total, COUNT(CASE WHEN stage=\'result\' THEN 1 END) as completed')
            ->groupBy('country_code')
            ->orderByDesc('total')
            ->get();

        return ApiResponse::success($data);
    }

    public function overdue(Request $request): JsonResponse
    {
        $agencyId = $this->agencyId($request);

        $cases = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->whereNotNull('critical_date')
            ->where('critical_date', '<', now())
            ->with(['client:id,name,phone', 'assignee:id,name'])
            ->orderBy('critical_date')
            ->get()
            ->map(function (VisaCase $case) {
                $daysOverdue = (int) now()->diffInDays($case->critical_date, false) * -1;
                return array_merge($case->toArray(), [
                    'days_overdue' => $daysOverdue,
                    'severity'     => $daysOverdue >= 7 ? 'critical' : 'warning',
                ]);
            });

        return ApiResponse::success($cases);
    }

    public function slaPerformance(Request $request): JsonResponse
    {
        $agencyId = $this->agencyId($request);

        // Среднее время (в часах) пребывания в каждом этапе
        $stageStats = CaseStage::join('cases', 'cases.id', '=', 'case_stages.case_id')
            ->where('cases.agency_id', $agencyId)
            ->whereNotNull('case_stages.exited_at')
            ->selectRaw('
                case_stages.stage,
                COUNT(*) as count,
                AVG(EXTRACT(EPOCH FROM (case_stages.exited_at - case_stages.entered_at))/3600) as avg_hours,
                MAX(EXTRACT(EPOCH FROM (case_stages.exited_at - case_stages.entered_at))/3600) as max_hours
            ')
            ->groupBy('case_stages.stage')
            ->get()
            ->map(function ($row) {
                return [
                    'stage'     => $row->stage,
                    'count'     => (int) $row->count,
                    'avg_hours' => round((float) $row->avg_hours, 1),
                    'avg_days'  => round((float) $row->avg_hours / 24, 1),
                    'max_hours' => round((float) $row->max_hours, 1),
                ];
            });

        return ApiResponse::success($stageStats);
    }
}
