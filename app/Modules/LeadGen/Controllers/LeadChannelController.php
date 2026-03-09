<?php

namespace App\Modules\LeadGen\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LeadGen\Models\AgencyLeadChannel;
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

        $connectedIds = AgencyLeadChannel::where('agency_id', $request->user()->agency_id)
            ->where('is_active', true)
            ->pluck('channel_id')
            ->toArray();

        $channels->each(function ($ch) use ($agencyPlan, $connectedIds) {
            $ch->available = $this->isPlanSufficient($agencyPlan, $ch->min_plan);
            $ch->connected = in_array($ch->id, $connectedIds);
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
        $channel->connected = AgencyLeadChannel::where('agency_id', $request->user()->agency_id)
            ->where('channel_id', $channel->id)
            ->where('is_active', true)
            ->exists();

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

    public function connect(Request $request, string $code): JsonResponse
    {
        $channel = LeadChannel::active()->where('code', $code)->firstOrFail();
        $agencyId = $request->user()->agency_id;
        $agencyPlan = $request->user()->agency?->effectivePlan() ?? 'starter';

        if (!$this->isPlanSufficient($agencyPlan, $channel->min_plan)) {
            return ApiResponse::error('Ваш тариф не позволяет подключить этот канал.', null, 403);
        }

        $connection = AgencyLeadChannel::withTrashed()
            ->where('agency_id', $agencyId)
            ->where('channel_id', $channel->id)
            ->first();

        if ($connection && !$connection->trashed()) {
            return ApiResponse::success($connection, 'Канал уже подключён.');
        }

        if ($connection && $connection->trashed()) {
            $connection->restore();
            $connection->update(['is_active' => true, 'connected_at' => now()]);
        } else {
            $connection = AgencyLeadChannel::create([
                'agency_id'    => $agencyId,
                'channel_id'   => $channel->id,
                'is_active'    => true,
                'connected_at' => now(),
            ]);
        }

        $this->trackView($request, $channel->id, 'click_connect');

        return ApiResponse::success([
            'channel_code' => $code,
            'connected'    => true,
            'connected_at' => $connection->connected_at,
        ], 'Канал подключён.');
    }

    public function disconnect(Request $request, string $code): JsonResponse
    {
        $channel = LeadChannel::where('code', $code)->firstOrFail();
        $agencyId = $request->user()->agency_id;

        $connection = AgencyLeadChannel::where('agency_id', $agencyId)
            ->where('channel_id', $channel->id)
            ->first();

        if (!$connection) {
            return ApiResponse::error('Канал не подключён.', null, 404);
        }

        $connection->delete();

        return ApiResponse::success(null, 'Канал отключён.');
    }

    public function connected(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $connections = AgencyLeadChannel::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->with('channel:id,code,name,name_uz,icon,category')
            ->get();

        return ApiResponse::success($connections);
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
