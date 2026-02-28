<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Services\BillingService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private readonly BillingService $billing) {}

    /**
     * GET /api/v1/billing/plans
     * Публичный список тарифов
     */
    public function plans(): JsonResponse
    {
        $plans = $this->billing->getPlans();

        return ApiResponse::success($plans->map(fn ($p) => [
            'slug'                 => $p->slug,
            'name'                 => $p->name,
            'price_monthly'        => $p->price_monthly,
            'price_monthly_usd'    => $p->priceMonthlyUsd(),
            'price_yearly'         => $p->price_yearly,
            'price_yearly_usd'     => $p->priceYearlyUsd(),
            'price_uzs'            => $p->price_uzs,
            'max_managers'         => $p->max_managers,
            'max_cases'            => $p->max_cases,
            'has_marketplace'      => $p->has_marketplace,
            'has_priority_support' => $p->has_priority_support,
            'features'             => $p->features,
            'is_unlimited_managers' => $p->isUnlimitedManagers(),
            'is_unlimited_cases'    => $p->isUnlimitedCases(),
        ]));
    }

    /**
     * GET /api/v1/billing/subscription
     * Текущая подписка агентства
     */
    public function subscription(Request $request): JsonResponse
    {
        $agency       = $request->user()->agency;
        $subscription = $this->billing->currentSubscription($agency);

        if (! $subscription) {
            return ApiResponse::notFound('No active subscription');
        }

        return ApiResponse::success([
            'id'             => $subscription->id,
            'plan_slug'      => $subscription->plan_slug,
            'plan'           => $subscription->plan,
            'status'         => $subscription->status,
            'billing_period' => $subscription->billing_period,
            'payment_method' => $subscription->payment_method,
            'starts_at'      => $subscription->starts_at,
            'expires_at'     => $subscription->expires_at,
            'days_left'      => $subscription->daysLeft(),
        ]);
    }

    /**
     * GET /api/v1/billing/limits
     * Лимиты текущего тарифа
     */
    public function limits(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $limits = $this->billing->checkPlanLimits($agency);

        return ApiResponse::success($limits);
    }

    /**
     * GET /api/v1/billing/transactions
     * История платежей
     */
    public function transactions(Request $request): JsonResponse
    {
        $agency       = $request->user()->agency;
        $transactions = $this->billing->transactions($agency);

        return ApiResponse::paginated($transactions);
    }

    /**
     * POST /api/v1/admin/billing/activate
     * Ручная активация плана (только superadmin)
     */
    public function adminActivate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agency_id'     => 'required|uuid|exists:agencies,id',
            'plan_slug'     => 'required|string|exists:billing_plans,slug',
            'billing_period' => 'required|in:monthly,yearly',
            'duration_days' => 'required|integer|min:1|max:3650',
        ]);

        $agency = \App\Modules\Agency\Models\Agency::findOrFail($validated['agency_id']);

        $durationDays = match ($validated['billing_period']) {
            'monthly' => $validated['duration_days'] ?? 30,
            'yearly'  => $validated['duration_days'] ?? 365,
            default   => $validated['duration_days'],
        };

        $subscription = $this->billing->activateManual(
            $agency,
            $validated['plan_slug'],
            $validated['billing_period'],
            $durationDays
        );

        return ApiResponse::created([
            'subscription_id' => $subscription->id,
            'plan'            => $subscription->plan_slug,
            'expires_at'      => $subscription->expires_at,
        ], 'Plan activated successfully');
    }

    /**
     * POST /api/v1/billing/cancel
     * Отмена подписки
     */
    public function cancel(Request $request): JsonResponse
    {
        $agency  = $request->user()->agency;
        $success = $this->billing->cancel($agency);

        if (! $success) {
            return ApiResponse::error('No active subscription to cancel');
        }

        return ApiResponse::success(null, 'Subscription cancelled');
    }
}
