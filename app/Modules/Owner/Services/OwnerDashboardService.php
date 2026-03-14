<?php

namespace App\Modules\Owner\Services;

use App\Modules\Agency\Models\Agency;
use Illuminate\Support\Facades\DB;

class OwnerDashboardService
{
    public function getStats(): array
    {
        return [
            'public_users' => [
                'total'   => DB::table('public_users')->count(),
                'today'   => DB::table('public_users')
                    ->whereDate('created_at', today())->count(),
                'week'    => DB::table('public_users')
                    ->whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            ],
            'leads' => [
                'total'      => DB::table('public_leads')->count(),
                'new'        => DB::table('public_leads')->where('status', 'new')->count(),
                'assigned'   => DB::table('public_leads')->where('status', 'assigned')->count(),
                'converted'  => DB::table('public_leads')->where('status', 'converted')->count(),
            ],
            'agencies' => [
                'total'    => Agency::count(),
                'active'   => Agency::where('is_active', true)->count(),
                'verified' => Agency::where('is_verified', true)->count(),
                'trial'    => Agency::where('plan', 'trial')->count(),
            ],
            'revenue' => [
                'total'       => round((DB::table('payment_transactions')
                    ->where('status', 'succeeded')->sum('amount') ?? 0) / 100, 2),
                'commissions' => 0,
                'this_month'  => round((DB::table('payment_transactions')
                    ->where('status', 'succeeded')
                    ->whereBetween('created_at', [now()->startOfMonth(), now()])->sum('amount') ?? 0) / 100, 2),
            ],
            'crm_users' => [
                'total'   => DB::table('cases')->count(),
                'clients' => DB::table('clients')->count(),
            ],
            'top_countries' => DB::table('public_leads')
                ->select('country_code', DB::raw('count(*) as count'))
                ->groupBy('country_code')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'avg_score' => (int) DB::table('public_leads')->avg('score'),
            'recent_agencies' => Agency::orderByDesc('created_at')->limit(5)
                ->get(['id', 'name', 'email', 'plan', 'is_active', 'created_at']),
        ];
    }
}
