<?php

namespace App\Modules\LeadGen\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LeadGen\Models\LeadChannel;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadChannelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LeadChannel::query()->orderBy('sort_order');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('complexity')) {
            $query->where('complexity', $request->input('complexity'));
        }

        if ($request->filled('min_plan')) {
            $query->where('min_plan', $request->input('min_plan'));
        }

        $agencyPlan = $request->user()->agency?->effectivePlan() ?? 'starter';

        if ($request->boolean('available_only')) {
            $available = $this->plansAtOrAbove($agencyPlan);
            $query->whereIn('min_plan', $available);
        }

        $channels = $query->get([
            'id', 'code', 'name', 'name_uz', 'category', 'icon',
            'short_description', 'short_description_uz',
            'effectiveness', 'effectiveness_factors',
            'complexity', 'launch_speed',
            'requires_budget', 'requires_api',
            'enterprise_only', 'min_plan',
            'recommended_for', 'coming_soon', 'is_active', 'sort_order',
        ]);

        $channels->each(function ($ch) use ($agencyPlan) {
            $ch->available = $this->isPlanSufficient($agencyPlan, $ch->min_plan);
        });

        $categories = LeadChannel::active()
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        return ApiResponse::success([
            'channels'    => $channels,
            'categories'  => $categories,
            'agency_plan' => $agencyPlan,
        ]);
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $channel = LeadChannel::where('code', $code)->firstOrFail();

        $agencyPlan = $request->user()->agency?->effectivePlan() ?? 'starter';
        $channel->available = $this->isPlanSufficient($agencyPlan, $channel->min_plan);

        $this->trackView($request, $channel->id, 'view');

        return ApiResponse::success([
            'channel'     => $channel,
            'agency_plan' => $agencyPlan,
        ]);
    }

    public function trackAction(Request $request, string $code): JsonResponse
    {
        $channel = LeadChannel::active()->where('code', $code)->firstOrFail();

        $action = $request->validate([
            'action' => ['required', 'in:view,click_details,click_cta,click_upgrade,click_connect'],
        ])['action'];

        $this->trackView($request, $channel->id, $action);

        return ApiResponse::success(null, 'Tracked.');
    }

    public function stats(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $stats = DB::table('lead_channel_views')
            ->where('agency_id', $agencyId)
            ->select('channel_id', 'action', DB::raw('COUNT(*) as count'))
            ->groupBy('channel_id', 'action')
            ->get()
            ->groupBy('channel_id')
            ->map(fn ($group) => $group->pluck('count', 'action'));

        return ApiResponse::success($stats);
    }

    private function trackView(Request $request, string $channelId, string $action): void
    {
        $user = $request->user();
        if (! $user?->agency_id) {
            return;
        }

        DB::table('lead_channel_views')->insert([
            'id'         => (string) Str::uuid(),
            'agency_id'  => $user->agency_id,
            'user_id'    => $user->id,
            'channel_id' => $channelId,
            'action'     => $action,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function isPlanSufficient(string $agencyPlan, string $requiredPlan): bool
    {
        $order = ['trial' => 0, 'starter' => 1, 'pro' => 2, 'enterprise' => 3];
        return ($order[$agencyPlan] ?? 0) >= ($order[$requiredPlan] ?? 0);
    }

    private function plansAtOrAbove(string $plan): array
    {
        $all = ['trial', 'starter', 'pro', 'enterprise'];
        $order = array_flip($all);
        $idx = $order[$plan] ?? 0;
        return array_slice($all, 0, $idx + 1);
    }
}
