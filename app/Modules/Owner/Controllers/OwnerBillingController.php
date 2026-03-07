<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\BillingEvent;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\Coupon;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\LedgerEntry;
use App\Modules\Payment\Models\PlanAddon;
use App\Modules\Payment\Models\PlatformSetting;
use App\Modules\Payment\Services\BillingEngine;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerBillingController extends Controller
{
    public function __construct(private readonly BillingEngine $engine) {}

    // =========================================================================
    // Тарифные планы
    // =========================================================================

    public function plans(): JsonResponse
    {
        $plans = BillingPlan::orderBy('sort_order')->get();

        $subscriberCounts = AgencySubscription::active()
            ->selectRaw('plan_slug, COUNT(*) as cnt')
            ->groupBy('plan_slug')
            ->pluck('cnt', 'plan_slug');

        $plans->each(fn ($p) => $p->subscribers_count = (int) ($subscriberCounts[$p->slug] ?? 0));

        return ApiResponse::success($plans);
    }

    public function planStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug'                     => 'required|string|max:50|unique:billing_plans,slug',
            'name'                     => 'required|string|max:100',
            'name_uz'                  => 'nullable|string|max:100',
            'description'              => 'nullable|string|max:1000',
            'price_monthly'            => 'required|integer|min:0',
            'price_yearly'             => 'required|integer|min:0',
            'price_uzs'               => 'required|integer|min:0',
            'activation_fee_uzs'       => 'integer|min:0',
            'earn_first_enabled'       => 'boolean',
            'earn_first_deduction_pct' => 'integer|min:0|max:100',
            'max_managers'             => 'required|integer|min:0',
            'max_cases'                => 'required|integer|min:0',
            'max_leads_per_month'      => 'integer|min:0',
            'has_marketplace'          => 'boolean',
            'has_priority_support'     => 'boolean',
            'has_api_access'           => 'boolean',
            'has_custom_domain'        => 'boolean',
            'has_white_label'          => 'boolean',
            'has_analytics'            => 'boolean',
            'trial_days'               => 'integer|min:0',
            'grace_period_days'        => 'integer|min:0',
            'features'                 => 'nullable|array',
            'is_active'                => 'boolean',
            'is_public'                => 'boolean',
            'is_recommended'           => 'boolean',
            'sort_order'               => 'integer',
        ]);

        $plan = BillingPlan::create($data);

        BillingEvent::log('plan.created', null, $request->user()?->id, 'billing_plan', $plan->slug, $data);

        return ApiResponse::created($plan);
    }

    public function planUpdate(Request $request, string $slug): JsonResponse
    {
        $plan = BillingPlan::findOrFail($slug);

        $data = $request->validate([
            'name'                     => 'sometimes|string|max:100',
            'name_uz'                  => 'nullable|string|max:100',
            'description'              => 'nullable|string|max:1000',
            'price_monthly'            => 'sometimes|integer|min:0',
            'price_yearly'             => 'sometimes|integer|min:0',
            'price_uzs'               => 'sometimes|integer|min:0',
            'activation_fee_uzs'       => 'integer|min:0',
            'earn_first_enabled'       => 'boolean',
            'earn_first_deduction_pct' => 'integer|min:0|max:100',
            'max_managers'             => 'sometimes|integer|min:0',
            'max_cases'                => 'sometimes|integer|min:0',
            'max_leads_per_month'      => 'integer|min:0',
            'has_marketplace'          => 'boolean',
            'has_priority_support'     => 'boolean',
            'has_api_access'           => 'boolean',
            'has_custom_domain'        => 'boolean',
            'has_white_label'          => 'boolean',
            'has_analytics'            => 'boolean',
            'trial_days'               => 'integer|min:0',
            'grace_period_days'        => 'integer|min:0',
            'features'                 => 'nullable|array',
            'is_active'                => 'boolean',
            'is_public'                => 'boolean',
            'is_recommended'           => 'boolean',
            'sort_order'               => 'integer',
        ]);

        $plan->update($data);

        BillingEvent::log('plan.updated', null, $request->user()?->id, 'billing_plan', $plan->slug, $data);

        return ApiResponse::success($plan);
    }

    public function planDestroy(string $slug): JsonResponse
    {
        $plan = BillingPlan::findOrFail($slug);

        $activeCount = AgencySubscription::where('plan_slug', $slug)->active()->count();
        if ($activeCount > 0) {
            return ApiResponse::error("Нельзя удалить план с {$activeCount} активными подписками", null, 409);
        }

        $plan->update(['is_active' => false]);

        return ApiResponse::success(null, 'План деактивирован');
    }

    // =========================================================================
    // Настройки платформы
    // =========================================================================

    public function settings(): JsonResponse
    {
        $settings = PlatformSetting::where('group', 'billing')->get()
            ->map(fn ($s) => [
                'key'         => $s->key,
                'value'       => PlatformSetting::get($s->key),
                'type'        => $s->type,
                'description' => $s->description,
            ]);

        return ApiResponse::success($settings);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings'       => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        foreach ($data['settings'] as $item) {
            PlatformSetting::set($item['key'], $item['value']);
        }

        BillingEvent::log('settings.updated', null, $request->user()?->id, 'platform_settings', null, $data['settings']);

        return ApiResponse::success(null, 'Настройки обновлены');
    }

    // =========================================================================
    // Купоны
    // =========================================================================

    public function coupons(): JsonResponse
    {
        $coupons = Coupon::orderByDesc('created_at')->get();
        return ApiResponse::success($coupons);
    }

    public function couponStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'           => 'required|string|max:30|unique:coupons,code',
            'description'    => 'nullable|string|max:255',
            'discount_type'  => 'required|in:percentage,fixed',
            'discount_value' => 'required|integer|min:1',
            'max_uses'       => 'integer|min:0',
            'plan_slug'      => 'nullable|string|exists:billing_plans,slug',
            'valid_from'     => 'nullable|date',
            'valid_until'    => 'nullable|date|after:valid_from',
            'is_active'      => 'boolean',
        ]);

        $coupon = Coupon::create($data);
        return ApiResponse::created($coupon);
    }

    public function couponUpdate(Request $request, string $id): JsonResponse
    {
        $coupon = Coupon::findOrFail($id);

        $data = $request->validate([
            'description'    => 'nullable|string|max:255',
            'discount_type'  => 'in:percentage,fixed',
            'discount_value' => 'integer|min:1',
            'max_uses'       => 'integer|min:0',
            'plan_slug'      => 'nullable|string|exists:billing_plans,slug',
            'valid_from'     => 'nullable|date',
            'valid_until'    => 'nullable|date',
            'is_active'      => 'boolean',
        ]);

        $coupon->update($data);
        return ApiResponse::success($coupon);
    }

    public function couponDestroy(string $id): JsonResponse
    {
        Coupon::findOrFail($id)->delete();
        return ApiResponse::success(null, 'Купон удалён');
    }

    // =========================================================================
    // Дашборд биллинга
    // =========================================================================

    public function dashboard(): JsonResponse
    {
        $revenue = DB::table('payment_transactions')
            ->where('status', 'succeeded')
            ->where('direction', 'inbound')
            ->selectRaw("
                SUM(amount) as total_revenue,
                SUM(vat_amount) as total_vat,
                SUM(CASE WHEN type = 'subscription' THEN amount ELSE 0 END) as subscription_revenue,
                SUM(CASE WHEN type = 'activation_fee' THEN amount ELSE 0 END) as activation_revenue,
                SUM(CASE WHEN type = 'commission' THEN amount ELSE 0 END) as commission_revenue
            ")
            ->first();

        $revenueThisMonth = DB::table('payment_transactions')
            ->where('status', 'succeeded')
            ->where('direction', 'inbound')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $subscriptions = DB::table('agency_subscriptions')
            ->selectRaw("
                status,
                COUNT(*) as count
            ")
            ->groupBy('status')
            ->pluck('count', 'status');

        $planDistribution = DB::table('agency_subscriptions')
            ->whereIn('status', ['active', 'trialing'])
            ->selectRaw('plan_slug, COUNT(*) as count')
            ->groupBy('plan_slug')
            ->pluck('count', 'plan_slug');

        $overdueInvoices = Invoice::where('status', 'issued')
            ->where('due_at', '<', now())
            ->count();

        $recentEvents = BillingEvent::orderByDesc('created_at')->limit(20)->get()
            ->map(function ($ev) {
                $agencyName = $ev->agency_id
                    ? DB::table('agencies')->where('id', $ev->agency_id)->value('name')
                    : null;
                $amount = $ev->metadata['amount'] ?? null;
                return [
                    'id'           => $ev->id,
                    'event'        => $ev->event,
                    'agency_id'    => $ev->agency_id,
                    'agency_name'  => $agencyName,
                    'amount'       => $amount,
                    'metadata'     => $ev->metadata,
                    'created_at'   => $ev->created_at,
                ];
            });

        return ApiResponse::success([
            'revenue'            => $revenue,
            'revenue_this_month' => $revenueThisMonth,
            'subscriptions'      => $subscriptions,
            'plan_distribution'  => $planDistribution,
            'overdue_invoices'   => $overdueInvoices,
            'recent_events'      => $recentEvents,
        ]);
    }

    // =========================================================================
    // Ручная активация подписки
    // =========================================================================

    public function activateSubscription(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agency_id'      => 'required|uuid|exists:agencies,id',
            'plan_slug'      => 'required|string|exists:billing_plans,slug',
            'billing_period' => 'required|in:monthly,yearly',
            'payment_model'  => 'in:prepaid,earn_first,hybrid',
            'coupon_code'    => 'nullable|string',
        ]);

        $agency = \App\Modules\Agency\Models\Agency::findOrFail($data['agency_id']);

        $subscription = $this->engine->subscribe(
            $agency,
            $data['plan_slug'],
            $data['billing_period'],
            $data['payment_model'] ?? 'prepaid',
            $data['coupon_code'] ?? null,
            $request->user()?->id,
        );

        return ApiResponse::created([
            'subscription_id' => $subscription->id,
            'plan'            => $subscription->plan_slug,
            'status'          => $subscription->status,
            'expires_at'      => $subscription->expires_at,
        ], 'Подписка активирована');
    }

    // =========================================================================
    // Аддоны
    // =========================================================================

    public function addons(): JsonResponse
    {
        return ApiResponse::success(PlanAddon::orderBy('sort_order')->get());
    }

    public function addonStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug'              => 'required|string|max:50|unique:plan_addons,slug',
            'name'              => 'required|string|max:100',
            'name_uz'           => 'nullable|string|max:100',
            'description'       => 'nullable|string',
            'price_monthly_uzs' => 'required|integer|min:0',
            'price_yearly_uzs'  => 'required|integer|min:0',
            'limits'            => 'nullable|array',
            'is_active'         => 'boolean',
            'sort_order'        => 'integer',
        ]);

        return ApiResponse::created(PlanAddon::create($data));
    }

    public function addonUpdate(Request $request, string $id): JsonResponse
    {
        $addon = PlanAddon::findOrFail($id);
        $addon->update($request->validated() ?: $request->all());
        return ApiResponse::success($addon);
    }

    public function addonDestroy(string $id): JsonResponse
    {
        PlanAddon::findOrFail($id)->update(['is_active' => false]);
        return ApiResponse::success(null, 'Аддон деактивирован');
    }

    // =========================================================================
    // Счета
    // =========================================================================

    public function invoices(Request $request): JsonResponse
    {
        $query = Invoice::with('agency:id,name')
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->has('agency_id')) {
            $query->where('agency_id', $request->get('agency_id'));
        }

        return ApiResponse::paginated($query->paginate(20));
    }
}
