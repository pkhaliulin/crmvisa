<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\BillingEvent;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\Invoice;
use Illuminate\Support\Facades\DB;

class PlanChangeService
{
    public function __construct(
        private readonly SubscriptionService  $subscriptionService,
        private readonly BillingHelperService $helper,
    ) {}

    /**
     * Смена тарифного плана с полной бизнес-логикой.
     *
     * Upgrade: немедленно, pro-rata кредит, счёт на разницу.
     * Downgrade: отложенный, вступает в конце текущего периода.
     */
    public function changePlan(
        Agency  $agency,
        string  $newPlanSlug,
        string  $billingPeriod = 'monthly',
        ?string $actorId = null,
    ): array {
        $newPlan = BillingPlan::findOrFail($newPlanSlug);
        $current = $this->subscriptionService->activeSubscription($agency);

        // Anti-abuse: не чаще 1 раза в 24 часа
        $lastChange = BillingEvent::where('agency_id', $agency->id)
            ->where('event', 'like', 'plan_change.%')
            ->where('created_at', '>', now()->subHours(24))
            ->exists();

        if ($lastChange) {
            throw new \RuntimeException('Смена тарифа доступна не чаще 1 раза в 24 часа');
        }

        // Trial / Free / нет подписки -> просто активируем
        if (! $current || in_array($current->status, ['trialing', 'expired', 'cancelled'])) {
            $subscription = $this->subscriptionService->subscribe($agency, $newPlanSlug, $billingPeriod, 'prepaid', null, $actorId);

            BillingEvent::log('plan_change.activated', $agency->id, $actorId, 'agency_subscription', $subscription->id, [
                'new_plan' => $newPlanSlug, 'period' => $billingPeriod,
            ]);

            return [
                'type'         => 'activated',
                'subscription' => $subscription,
                'credit'       => 0,
                'charge'       => 0,
                'warnings'     => [],
            ];
        }

        $changeType = $this->classifyPlanChange($current, $newPlan, $billingPeriod);

        if ($changeType === 'upgrade') {
            return $this->executeUpgrade($agency, $current, $newPlan, $billingPeriod, $actorId);
        }

        return $this->scheduleDowngrade($agency, $current, $newPlan, $billingPeriod, $actorId);
    }

    /**
     * Определяет тип смены: upgrade, downgrade, period_change.
     */
    public function classifyPlanChange(
        AgencySubscription $current,
        BillingPlan $newPlan,
        string $newPeriod,
    ): string {
        $currentPlan = $current->plan;

        // Тот же план, смена периода
        if ($current->plan_slug === $newPlan->slug) {
            return $current->billing_period === 'monthly' && $newPeriod === 'yearly'
                ? 'upgrade'
                : 'downgrade';
        }

        $currentPrice = $currentPlan ? $currentPlan->price_uzs : 0;
        $newPrice     = $newPlan->price_uzs;

        return $newPrice > $currentPrice ? 'upgrade' : 'downgrade';
    }

    /**
     * Рассчитывает pro-rata кредит за неиспользованный остаток текущей подписки.
     */
    public function calculateProRataCredit(AgencySubscription $subscription): int
    {
        if (! $subscription->expires_at || ! $subscription->starts_at) {
            return 0;
        }

        $plan = $subscription->plan;
        if (! $plan) return 0;

        $totalPrice = $subscription->billing_period === 'yearly'
            ? $plan->price_yearly
            : $plan->price_uzs;

        // Учитываем скидку
        $totalPrice = max(0, $totalPrice - ($subscription->discount_amount ?? 0));

        // Earn-first: кредит = уже списанная сумма
        if ($subscription->isEarnFirst()) {
            return $subscription->earn_first_deducted_total;
        }

        $totalDays     = max(1, $subscription->starts_at->diffInDays($subscription->expires_at));
        $remainingDays = max(0, (int) now()->diffInDays($subscription->expires_at, false));

        if ($remainingDays <= 0) return 0;

        return (int) round($totalPrice * $remainingDays / $totalDays);
    }

    /**
     * Проверяет лимиты нового плана vs текущее использование.
     * Возвращает массив предупреждений (пустой = всё ОК).
     */
    public function checkDowngradeLimits(Agency $agency, BillingPlan $newPlan): array
    {
        $warnings = [];

        $managersCount = $agency->users()->whereIn('role', ['owner', 'manager'])->count();
        $casesCount    = \App\Modules\Case\Models\VisaCase::where('agency_id', $agency->id)
            ->whereNotIn('stage', ['result'])
            ->count();

        if ($newPlan->max_managers > 0 && $managersCount > $newPlan->max_managers) {
            $warnings[] = "У вас {$managersCount} менеджеров, на плане {$newPlan->name} максимум {$newPlan->max_managers}. Деактивируйте лишних до окончания текущего периода.";
        }

        if ($newPlan->max_cases > 0 && $casesCount > $newPlan->max_cases) {
            $warnings[] = "У вас {$casesCount} активных заявок, на плане {$newPlan->name} максимум {$newPlan->max_cases}.";
        }

        return $warnings;
    }

    /**
     * Немедленный upgrade с pro-rata кредитом.
     */
    public function executeUpgrade(
        Agency $agency,
        AgencySubscription $current,
        BillingPlan $newPlan,
        string $billingPeriod,
        ?string $actorId,
    ): array {
        return DB::transaction(function () use ($agency, $current, $newPlan, $billingPeriod, $actorId) {
            $credit = $this->calculateProRataCredit($current);

            // Отменяем текущую
            $this->subscriptionService->cancelCurrentSubscription($agency);

            // Считаем стоимость нового плана
            $newPrice = $billingPeriod === 'yearly' ? $newPlan->price_yearly : $newPlan->price_uzs;
            $charge   = max(0, $newPrice - $credit);

            // Создаём новую подписку
            $durationDays = $billingPeriod === 'yearly' ? 365 : 30;
            $expiresAt    = now()->addDays($durationDays);

            $subscription = AgencySubscription::create([
                'agency_id'                  => $agency->id,
                'plan_slug'                  => $newPlan->slug,
                'status'                     => 'active',
                'billing_period'             => $billingPeriod,
                'payment_method'             => 'manual',
                'payment_model'              => 'prepaid',
                'earn_first_deducted_total'  => 0,
                'earn_first_target'          => 0,
                'activation_fee_paid'        => true,
                'activation_paid_at'         => now(),
                'starts_at'                  => now(),
                'expires_at'                 => $expiresAt,
                'grace_ends_at'              => $expiresAt->copy()->addDays($newPlan->grace_period_days),
                'auto_renew'                 => true,
                'discount_amount'            => $credit,
            ]);

            $agency->update([
                'plan'            => $newPlan->slug,
                'plan_expires_at' => $expiresAt,
            ]);

            // Счёт на разницу (если есть что платить)
            if ($charge > 0) {
                $vatInfo = $this->helper->calculateVat($charge);
                Invoice::create([
                    'agency_id'       => $agency->id,
                    'subscription_id' => $subscription->id,
                    'number'          => Invoice::generateNumber(),
                    'type'            => 'subscription',
                    'status'          => 'issued',
                    'subtotal'        => $charge,
                    'vat_amount'      => $vatInfo['vat'],
                    'discount_amount' => $credit,
                    'total'           => $charge + $vatInfo['vat'],
                    'currency'        => 'UZS',
                    'line_items'      => [
                        [
                            'description' => "Upgrade: {$newPlan->name} ({$billingPeriod})",
                            'quantity'    => 1,
                            'unit_price'  => $newPrice,
                            'discount'    => $credit,
                            'total'       => $charge,
                        ],
                    ],
                    'issued_at' => now(),
                    'due_at'    => now()->addDays(7),
                ]);
            }

            BillingEvent::log('plan_change.upgraded', $agency->id, $actorId, 'agency_subscription', $subscription->id, [
                'old_plan' => $current->plan_slug,
                'new_plan' => $newPlan->slug,
                'credit'   => $credit,
                'charge'   => $charge,
                'period'   => $billingPeriod,
            ]);

            return [
                'type'         => 'upgrade',
                'subscription' => $subscription,
                'credit'       => $credit,
                'charge'       => $charge,
                'warnings'     => [],
            ];
        });
    }

    /**
     * Отложенный downgrade — запланирован на конец текущего периода.
     */
    public function scheduleDowngrade(
        Agency $agency,
        AgencySubscription $current,
        BillingPlan $newPlan,
        string $billingPeriod,
        ?string $actorId,
    ): array {
        $warnings = $this->checkDowngradeLimits($agency, $newPlan);
        $changeAt = $current->expires_at ?? now()->addDays(30);

        $current->update([
            'pending_plan_slug'      => $newPlan->slug,
            'pending_billing_period' => $billingPeriod,
            'pending_change_at'      => $changeAt,
        ]);

        BillingEvent::log('plan_change.downgrade_scheduled', $agency->id, $actorId, 'agency_subscription', $current->id, [
            'old_plan'  => $current->plan_slug,
            'new_plan'  => $newPlan->slug,
            'period'    => $billingPeriod,
            'change_at' => $changeAt->toDateTimeString(),
            'warnings'  => $warnings,
        ]);

        return [
            'type'         => 'downgrade_scheduled',
            'subscription' => $current,
            'credit'       => 0,
            'charge'       => 0,
            'change_at'    => $changeAt,
            'warnings'     => $warnings,
        ];
    }

    /**
     * Применить запланированные downgrade (вызывается из scheduler).
     */
    public function applyPendingDowngrades(): int
    {
        $count = 0;

        $pending = AgencySubscription::whereNotNull('pending_plan_slug')
            ->where('pending_change_at', '<=', now())
            ->whereIn('status', ['active', 'trialing', 'past_due', 'expired'])
            ->get();

        foreach ($pending as $sub) {
            try {
                DB::transaction(function () use ($sub) {
                    $newPlanSlug = $sub->pending_plan_slug;
                    $newPeriod   = $sub->pending_billing_period ?? 'monthly';
                    $agency      = $sub->agency;

                    // Отменяем текущую
                    $sub->update(['status' => 'cancelled', 'cancelled_at' => now()]);

                    // Активируем новый план
                    $this->subscriptionService->subscribe($agency, $newPlanSlug, $newPeriod, 'prepaid');

                    BillingEvent::log('plan_change.downgrade_applied', $agency->id, null, 'agency_subscription', $sub->id, [
                        'old_plan' => $sub->plan_slug,
                        'new_plan' => $newPlanSlug,
                    ]);
                });
                $count++;
            } catch (\Throwable $e) {
                \Log::error("Failed to apply pending downgrade for subscription {$sub->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Отменить запланированный downgrade.
     */
    public function cancelPendingDowngrade(Agency $agency, ?string $actorId = null): bool
    {
        $subscription = $this->subscriptionService->activeSubscription($agency);
        if (! $subscription || ! $subscription->hasPendingDowngrade()) {
            return false;
        }

        $oldPending = $subscription->pending_plan_slug;
        $subscription->cancelPendingDowngrade();

        BillingEvent::log('plan_change.downgrade_cancelled', $agency->id, $actorId, 'agency_subscription', $subscription->id, [
            'cancelled_plan' => $oldPending,
        ]);

        return true;
    }
}
