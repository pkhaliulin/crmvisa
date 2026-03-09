<?php

namespace App\Modules\LeadGen\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadAnalyticsController extends Controller
{
    /**
     * Get lead analytics by source and channel.
     */
    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $period = $request->input('period', '30');
        $since = now()->subDays((int) $period);

        // By lead_source
        $bySource = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->where('stage', 'lead')
            ->where('created_at', '>=', $since)
            ->whereNull('deleted_at')
            ->select('lead_source', DB::raw('COUNT(*) as count'))
            ->groupBy('lead_source')
            ->orderByDesc('count')
            ->get();

        // By lead_channel_code
        $byChannel = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->whereNotNull('lead_channel_code')
            ->where('created_at', '>=', $since)
            ->whereNull('deleted_at')
            ->select('lead_channel_code', DB::raw('COUNT(*) as count'))
            ->groupBy('lead_channel_code')
            ->orderByDesc('count')
            ->get();

        // Total leads in period
        $totalLeads = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->where('created_at', '>=', $since)
            ->whereNull('deleted_at')
            ->count();

        // Leads by stage (conversion funnel)
        $byStage = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->where('created_at', '>=', $since)
            ->whereNull('deleted_at')
            ->select('stage', DB::raw('COUNT(*) as count'))
            ->groupBy('stage')
            ->get();

        // Daily trend
        $dailyTrend = DB::table('cases')
            ->where('agency_id', $agencyId)
            ->where('created_at', '>=', $since)
            ->whereNull('deleted_at')
            ->select(DB::raw("DATE(created_at) as date"), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return ApiResponse::success([
            'period'      => (int) $period,
            'total_leads' => $totalLeads,
            'by_source'   => $bySource,
            'by_channel'  => $byChannel,
            'by_stage'    => $byStage,
            'daily_trend' => $dailyTrend,
        ]);
    }
}
