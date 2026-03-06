<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Services\BillingEngine;
use App\Modules\Payment\Services\BillingService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        private readonly BillingService $billing,
        private readonly BillingEngine  $engine,
    ) {}

    /**
     * GET /api/v1/billing/plans
     */
    public function plans(): JsonResponse
    {
        $plans = $this->billing->getPlans();

        return ApiResponse::success($plans->map(fn ($p) => [
            'slug'                     => $p->slug,
            'name'                     => $p->name,
            'name_uz'                  => $p->name_uz,
            'description'              => $p->description,
            'price_monthly'            => $p->price_monthly,
            'price_monthly_usd'        => $p->priceMonthlyUsd(),
            'price_yearly'             => $p->price_yearly,
            'price_yearly_usd'         => $p->priceYearlyUsd(),
            'price_uzs'                => $p->price_uzs,
            'activation_fee_uzs'       => $p->activation_fee_uzs,
            'earn_first_enabled'       => $p->earn_first_enabled,
            'earn_first_deduction_pct' => $p->earn_first_deduction_pct,
            'max_managers'             => $p->max_managers,
            'max_cases'                => $p->max_cases,
            'max_leads_per_month'      => $p->max_leads_per_month,
            'has_marketplace'          => $p->has_marketplace,
            'has_priority_support'     => $p->has_priority_support,
            'has_api_access'           => $p->has_api_access,
            'has_custom_domain'        => $p->has_custom_domain,
            'has_white_label'          => $p->has_white_label,
            'has_analytics'            => $p->has_analytics,
            'trial_days'               => $p->trial_days,
            'features'                 => $p->features,
            'is_unlimited_managers'    => $p->isUnlimitedManagers(),
            'is_unlimited_cases'       => $p->isUnlimitedCases(),
            'is_recommended'           => $p->is_recommended,
        ]));
    }

    /**
     * GET /api/v1/billing/subscription
     */
    public function subscription(Request $request): JsonResponse
    {
        $agency       = $request->user()->agency;
        $subscription = $this->billing->currentSubscription($agency);

        if (! $subscription) {
            $planValue = $agency->plan instanceof \BackedEnum
                ? $agency->plan->value
                : (string) $agency->plan;

            return ApiResponse::success([
                'id'              => null,
                'plan_slug'       => $planValue,
                'status'          => 'active',
                'billing_period'  => null,
                'payment_method'  => null,
                'payment_model'   => 'prepaid',
                'starts_at'       => $agency->created_at,
                'expires_at'      => $agency->plan_expires_at,
                'grace_ends_at'   => null,
                'days_left'       => null,
                'activation_fee_paid' => true,
                'earn_first_progress' => null,
            ]);
        }

        $earnFirstProgress = null;
        if ($subscription->isEarnFirst() && $subscription->earn_first_target > 0) {
            $earnFirstProgress = [
                'deducted' => $subscription->earn_first_deducted_total,
                'target'   => $subscription->earn_first_target,
                'pct'      => min(100, (int) round($subscription->earn_first_deducted_total / $subscription->earn_first_target * 100)),
            ];
        }

        return ApiResponse::success([
            'id'              => $subscription->id,
            'plan_slug'       => $subscription->plan_slug,
            'plan'            => $subscription->plan,
            'status'          => $subscription->status,
            'billing_period'  => $subscription->billing_period,
            'payment_method'  => $subscription->payment_method,
            'payment_model'   => $subscription->payment_model,
            'starts_at'       => $subscription->starts_at,
            'expires_at'      => $subscription->expires_at,
            'grace_ends_at'   => $subscription->grace_ends_at,
            'days_left'       => $subscription->daysLeft(),
            'auto_renew'      => $subscription->auto_renew,
            'activation_fee_paid' => $subscription->activation_fee_paid,
            'earn_first_progress' => $earnFirstProgress,
            'is_in_grace_period'  => $subscription->isInGracePeriod(),
        ]);
    }

    /**
     * GET /api/v1/billing/limits
     */
    public function limits(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $limits = $this->engine->checkLimits($agency);

        return ApiResponse::success($limits);
    }

    /**
     * GET /api/v1/billing/wallet
     */
    public function wallet(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $wallet = $this->engine->getWallet($agency);

        return ApiResponse::success([
            'balance'        => $wallet->balance,
            'total_earned'   => $wallet->total_earned,
            'total_deducted' => $wallet->total_deducted,
            'total_paid_out' => $wallet->total_paid_out,
            'pending_payout' => $wallet->pending_payout,
            'last_payout_at' => $wallet->last_payout_at,
        ]);
    }

    /**
     * GET /api/v1/billing/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $agency       = $request->user()->agency;
        $transactions = $this->billing->transactions($agency);

        return ApiResponse::paginated($transactions);
    }

    /**
     * GET /api/v1/billing/invoices
     */
    public function invoices(Request $request): JsonResponse
    {
        $agency  = $request->user()->agency;
        $invoices = Invoice::where('agency_id', $agency->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponse::paginated($invoices);
    }

    /**
     * POST /api/v1/billing/cancel
     */
    public function cancel(Request $request): JsonResponse
    {
        $agency  = $request->user()->agency;
        $success = $this->billing->cancel($agency);

        if (! $success) {
            return ApiResponse::error('Нет активной подписки');
        }

        return ApiResponse::success(null, 'Подписка отменена');
    }

    /**
     * POST /api/v1/admin/billing/activate
     */
    public function adminActivate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agency_id'      => 'required|uuid|exists:agencies,id',
            'plan_slug'      => 'required|string|exists:billing_plans,slug',
            'billing_period' => 'required|in:monthly,yearly',
            'payment_model'  => 'in:prepaid,earn_first,hybrid',
            'coupon_code'    => 'nullable|string',
        ]);

        $agency = \App\Modules\Agency\Models\Agency::findOrFail($validated['agency_id']);

        $subscription = $this->engine->subscribe(
            $agency,
            $validated['plan_slug'],
            $validated['billing_period'],
            $validated['payment_model'] ?? 'prepaid',
            $validated['coupon_code'] ?? null,
            $request->user()?->id,
        );

        return ApiResponse::created([
            'subscription_id' => $subscription->id,
            'plan'            => $subscription->plan_slug,
            'expires_at'      => $subscription->expires_at,
        ], 'Подписка активирована');
    }
}
