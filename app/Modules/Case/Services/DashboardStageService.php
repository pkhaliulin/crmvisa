<?php

namespace App\Modules\Case\Services;

use Illuminate\Support\Facades\DB;

class DashboardStageService
{
    public function getStageAnalytics(string $agencyId): array
    {
        // SLA нормы из config
        $slaNorms = [];
        foreach (config('stages') as $key => $cfg) {
            $slaNorms[$key] = $cfg['sla_hours'] ?? null;
        }

        // Аналитика по этапам (из case_stages)
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

        // Среднее время обработки
        $avgProcessingHours = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->where('stage', 'result')
            ->whereNotNull('updated_at')
            ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 3600), 1) as avg_hours")
            ->value('avg_hours');

        return [
            'stage_analytics'      => $stageAnalytics,
            'sla_norms'            => $slaNorms,
            'avg_processing_hours' => (float) ($avgProcessingHours ?? 0),
        ];
    }
}
