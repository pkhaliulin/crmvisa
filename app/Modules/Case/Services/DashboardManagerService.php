<?php

namespace App\Modules\Case\Services;

use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardManagerService
{
    public function getManagerAnalytics(string $agencyId): array
    {
        $excludeUnpaidSql = "cases.public_status NOT IN ('draft','awaiting_payment','cancelled') OR cases.public_status IS NULL";

        $managerLoad = User::where('users.agency_id', $agencyId)
            ->where('users.is_active', true)
            ->whereIn('users.role', ['manager', 'owner'])
            ->leftJoin('cases', function ($join) use ($excludeUnpaidSql) {
                $join->on('cases.assigned_to', '=', 'users.id')
                     ->whereRaw("({$excludeUnpaidSql})");
            })
            ->select(
                'users.id',
                'users.name',
                'users.role',
                DB::raw("COUNT(CASE WHEN cases.id IS NOT NULL AND cases.stage != 'result' THEN 1 END) as active_cases"),
                DB::raw("COUNT(CASE WHEN cases.id IS NOT NULL AND cases.stage = 'result' THEN 1 END) as completed_cases"),
                DB::raw("COUNT(CASE WHEN cases.id IS NOT NULL AND cases.stage = 'result' AND cases.result_type = 'approved' THEN 1 END) as approved_cases"),
                DB::raw("COUNT(CASE WHEN cases.id IS NOT NULL AND cases.critical_date IS NOT NULL AND cases.critical_date < CURRENT_DATE AND cases.stage != 'result' THEN 1 END) as overdue_cases"),
                DB::raw("ROUND(AVG(CASE WHEN cases.stage = 'result' THEN EXTRACT(EPOCH FROM (cases.updated_at - cases.created_at)) / 3600 END), 1) as avg_hours"),
            )
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('active_cases')
            ->get()
            ->filter(function ($m) {
                if ($m->role === 'owner') {
                    return ($m->active_cases + $m->completed_cases) > 0;
                }
                return true;
            })
            ->values()
            ->map(function ($m) {
                $total = $m->active_cases + $m->completed_cases;
                $m->conversion = $total > 0
                    ? round($m->approved_cases / $total * 100, 1)
                    : 0;
                $m->avg_hours = (float) ($m->avg_hours ?? 0);
                return $m;
            });

        return [
            'managers'       => $managerLoad,
            'managers_count' => $managerLoad->count(),
        ];
    }
}
