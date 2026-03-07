<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Models\AgencyAddon;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\AgencyWallet;
use App\Modules\Payment\Models\BillingEvent;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\Coupon;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\LedgerEntry;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Payment\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingEngine
{
    // =========================================================================
    // Подписки
    // =========================================================================

    /**
     * Создать/активировать подписку для агентства.
     */
    public function subscribe(
        Agency  $agency,
        string  $planSlug,
        string  $billingPeriod = 'monthly',
        string  $paymentModel = 'prepaid',
        ?string $couponCode = null,
        ?string $actorId = null,
    ): AgencySubscription {
        $plan = BillingPlan::findOrFail($planSlug);

        return DB::transaction(function () use ($agency, $plan, $billingPeriod, $paymentModel, $couponCode, $actorId) {
            // Отменяем текущую
            $this->cancelCurrentSubscription($agency);

            $coupon   = $couponCode ? Coupon::where('code', $couponCode)->first() : null;
            $discount = 0;
            if ($coupon && $coupon->isValid($plan->slug)) {
                $price    = $billingPeriod === 'yearly' ? $plan->price_yearly : $plan->price_uzs;
                $discount = $coupon->calculateDiscount($price);
                $coupon->incrementUsage();
            }

            $durationDays = $billingPeriod === 'yearly' ? 365 : 30;
            $isTrial      = $plan->trial_days > 0 && ! $this->hasHadSubscription($agency);
            $status       = $isTrial ? 'trialing' : 'active';

            if ($isTrial) {
                $durationDays = $plan->trial_days;
            }

            $expiresAt  = now()->addDays($durationDays);
            $graceEndsAt = $expiresAt->copy()->addDays($plan->grace_period_days);

            // Earn-first: целевая сумма подписки
            $earnFirstTarget = 0;
            if ($paymentModel === 'earn_first' && $plan->earn_first_enabled) {
                $basePrice = $billingPeriod === 'yearly' ? $plan->price_yearly : $plan->price_uzs;
                $earnFirstTarget = max(0, $basePrice - $discount);
            }

            $subscription = AgencySubscription::create([
                'agency_id'                  => $agency->id,
                'plan_slug'                  => $plan->slug,
                'status'                     => $status,
                'billing_period'             => $billingPeriod,
                'payment_method'             => 'manual',
                'payment_model'              => $paymentModel,
                'earn_first_deducted_total'  => 0,
                'earn_first_target'          => $earnFirstTarget,
                'activation_fee_paid'        => true, // TODO: false когда подключим платёжный шлюз
                'activation_paid_at'         => now(),
                'starts_at'                  => now(),
                'expires_at'                 => $expiresAt,
                'grace_ends_at'              => $graceEndsAt,
                'auto_renew'                 => true,
                'coupon_id'                  => $coupon?->id,
                'discount_amount'            => $discount,
            ]);

            // Синхронизация с Agency
            $agency->update([
                'plan'            => $plan->slug,
                'plan_expires_at' => $expiresAt,
            ]);

            // Кошелёк
            AgencyWallet::firstOrCreate(
                ['agency_id' => $agency->id],
                ['balance' => 0, 'total_earned' => 0, 'total_deducted' => 0, 'total_paid_out' => 0, 'pending_payout' => 0]
            );

            // Счёт-фактура (для prepaid)
            if ($paymentModel === 'prepaid' && ! $isTrial) {
                $this->createSubscriptionInvoice($subscription, $plan, $discount);
            }

            BillingEvent::log(
                'subscription.created',
                $agency->id,
                $actorId,
                'agency_subscription',
                $subscription->id,
                ['plan' => $plan->slug, 'model' => $paymentModel, 'period' => $billingPeriod],
            );

            return $subscription;
        });
    }

    /**
     * Оплата активационного сбора.
     */
    public function payActivationFee(
        Agency $agency,
        string $provider = 'manual',
        ?string $providerTxId = null,
    ): PaymentTransaction {
        $subscription = $this->activeSubscription($agency);
        if (! $subscription) {
            throw new \RuntimeException('Нет активной подписки');
        }

        $plan = $subscription->plan;
        if ($plan->activation_fee_uzs <= 0 || $subscription->activation_fee_paid) {
            throw new \RuntimeException('Активационный сбор не требуется или уже оплачен');
        }

        return DB::transaction(function () use ($agency, $subscription, $plan, $provider, $providerTxId) {
            $vatInfo = $this->calculateVat($plan->activation_fee_uzs);

            $transaction = PaymentTransaction::create([
                'agency_id'               => $agency->id,
                'subscription_id'         => $subscription->id,
                'type'                    => 'activation_fee',
                'direction'               => 'inbound',
                'provider'                => $provider,
                'provider_transaction_id' => $providerTxId,
                'amount'                  => $plan->activation_fee_uzs,
                'vat_amount'              => $vatInfo['vat'],
                'vat_rate'                => $vatInfo['rate'],
                'currency'                => 'UZS',
                'status'                  => 'succeeded',
                'description'             => "Активационный сбор: {$plan->name}",
                'paid_at'                 => now(),
            ]);

            $subscription->update([
                'activation_fee_paid' => true,
                'activation_paid_at'  => now(),
            ]);

            // Двойная запись: деньги от агентства -> доход платформы
            LedgerEntry::record(
                'agency_payment',
                'platform_revenue',
                $plan->activation_fee_uzs,
                $transaction->id,
                $agency->id,
                'Активационный сбор',
            );

            if ($vatInfo['vat'] > 0) {
                LedgerEntry::record('platform_revenue', 'vat_payable', $vatInfo['vat'], $transaction->id, $agency->id, 'НДС с активационного сбора');
            }

            BillingEvent::log('activation_fee.paid', $agency->id, null, 'payment_transaction', $transaction->id, [
                'amount' => $plan->activation_fee_uzs,
            ]);

            return $transaction;
        });
    }

    /**
     * Оплата подписки (prepaid).
     */
    public function paySubscription(
        Agency  $agency,
        string  $provider = 'manual',
        ?string $providerTxId = null,
    ): PaymentTransaction {
        $subscription = $this->activeSubscription($agency);
        if (! $subscription) {
            throw new \RuntimeException('Нет активной подписки');
        }

        $plan  = $subscription->plan;
        $price = $subscription->billing_period === 'yearly' ? $plan->price_yearly : $plan->price_uzs;
        $amount = max(0, $price - $subscription->discount_amount);

        return DB::transaction(function () use ($agency, $subscription, $amount, $provider, $providerTxId) {
            $vatInfo = $this->calculateVat($amount);

            $transaction = PaymentTransaction::create([
                'agency_id'               => $agency->id,
                'subscription_id'         => $subscription->id,
                'type'                    => 'subscription',
                'direction'               => 'inbound',
                'provider'                => $provider,
                'provider_transaction_id' => $providerTxId,
                'amount'                  => $amount,
                'vat_amount'              => $vatInfo['vat'],
                'vat_rate'                => $vatInfo['rate'],
                'currency'                => 'UZS',
                'status'                  => 'succeeded',
                'description'             => "Подписка: {$subscription->plan_slug} ({$subscription->billing_period})",
                'paid_at'                 => now(),
            ]);

            // Обновить статус подписки
            if ($subscription->status === 'past_due') {
                $subscription->update(['status' => 'active']);
            }

            // Оплатить счёт
            $invoice = Invoice::where('subscription_id', $subscription->id)
                ->where('status', 'issued')
                ->first();

            if ($invoice) {
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            }

            LedgerEntry::record('agency_payment', 'platform_revenue', $amount, $transaction->id, $agency->id, 'Оплата подписки');

            if ($vatInfo['vat'] > 0) {
                LedgerEntry::record('platform_revenue', 'vat_payable', $vatInfo['vat'], $transaction->id, $agency->id, 'НДС с подписки');
            }

            BillingEvent::log('subscription.paid', $agency->id, null, 'payment_transaction', $transaction->id, ['amount' => $amount]);

            return $transaction;
        });
    }

    /**
     * Earn-first удержание: вызывается после завершения заказа.
     */
    public function deductEarnFirst(Agency $agency, int $orderAmount, string $orderId): ?PaymentTransaction
    {
        $subscription = $this->activeSubscription($agency);
        if (! $subscription || ! $subscription->isEarnFirst()) return null;

        $remaining = $subscription->earn_first_target - $subscription->earn_first_deducted_total;
        if ($remaining <= 0) return null;

        $deductionPct = $subscription->plan->earn_first_deduction_pct;
        $deduction    = (int) round($orderAmount * $deductionPct / 100);
        $deduction    = min($deduction, $remaining); // Не вычитать больше, чем осталось

        if ($deduction <= 0) return null;

        return DB::transaction(function () use ($agency, $subscription, $deduction, $orderId) {
            $transaction = PaymentTransaction::create([
                'agency_id'       => $agency->id,
                'subscription_id' => $subscription->id,
                'type'            => 'earn_first_deduction',
                'direction'       => 'inbound',
                'provider'        => 'internal',
                'amount'          => $deduction,
                'currency'        => 'UZS',
                'status'          => 'succeeded',
                'description'     => "Удержание earn-first из заказа {$orderId}",
                'metadata'        => ['order_id' => $orderId],
                'paid_at'         => now(),
            ]);

            $subscription->increment('earn_first_deducted_total', $deduction);

            // Удержать из кошелька
            $wallet = AgencyWallet::where('agency_id', $agency->id)->first();
            $wallet?->deduct($deduction, "Earn-first удержание");

            LedgerEntry::record('agency_wallet', 'platform_revenue', $deduction, $transaction->id, $agency->id, 'Earn-first удержание');

            BillingEvent::log('earn_first.deducted', $agency->id, null, 'payment_transaction', $transaction->id, [
                'deduction' => $deduction,
                'total_deducted' => $subscription->earn_first_deducted_total,
                'target'         => $subscription->earn_first_target,
            ]);

            return $transaction;
        });
    }

    /**
     * Начислить заработок агентству (когда клиент оплатил заказ).
     */
    public function creditAgencyEarnings(Agency $agency, int $amount, string $caseId, string $description = ''): void
    {
        DB::transaction(function () use ($agency, $amount, $caseId, $description) {
            $commissionPct = (float) PlatformSetting::get('platform_commission', 5);
            $commission    = (int) round($amount * $commissionPct / 100);
            $netAmount     = $amount - $commission;

            $wallet = AgencyWallet::firstOrCreate(
                ['agency_id' => $agency->id],
                ['balance' => 0, 'total_earned' => 0, 'total_deducted' => 0, 'total_paid_out' => 0, 'pending_payout' => 0]
            );

            $wallet->credit($netAmount, $description);

            // Комиссия
            if ($commission > 0) {
                $tx = PaymentTransaction::create([
                    'agency_id'   => $agency->id,
                    'type'        => 'commission',
                    'direction'   => 'inbound',
                    'provider'    => 'internal',
                    'amount'      => $commission,
                    'currency'    => 'UZS',
                    'status'      => 'succeeded',
                    'description' => "Комиссия платформы {$commissionPct}% с заказа {$caseId}",
                    'paid_at'     => now(),
                ]);

                LedgerEntry::record('client_payment', 'platform_revenue', $commission, $tx->id, $agency->id, 'Комиссия платформы');
            }

            LedgerEntry::record('client_payment', 'agency_wallet', $netAmount, null, $agency->id, "Заработок: {$description}");

            // Earn-first удержание
            $this->deductEarnFirst($agency, $netAmount, $caseId);
        });
    }

    // =========================================================================
    // Смена тарифа (upgrade / downgrade)
    // =========================================================================

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
                ? 'upgrade'   // monthly->yearly = выгоднее
                : 'downgrade'; // yearly->monthly
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
        $current = $this->activeSubscription($agency);

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
            $subscription = $this->subscribe($agency, $newPlanSlug, $billingPeriod, 'prepaid', null, $actorId);

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
     * Немедленный upgrade с pro-rata кредитом.
     */
    private function executeUpgrade(
        Agency $agency,
        AgencySubscription $current,
        BillingPlan $newPlan,
        string $billingPeriod,
        ?string $actorId,
    ): array {
        return DB::transaction(function () use ($agency, $current, $newPlan, $billingPeriod, $actorId) {
            $credit = $this->calculateProRataCredit($current);

            // Отменяем текущую
            $this->cancelCurrentSubscription($agency);

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
                'activation_fee_paid'        => true, // TODO: false когда подключим платёжный шлюз
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
                $vatInfo = $this->calculateVat($charge);
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
    private function scheduleDowngrade(
        Agency $agency,
        AgencySubscription $current,
        BillingPlan $newPlan,
        string $billingPeriod,
        ?string $actorId,
    ): array {
        $warnings  = $this->checkDowngradeLimits($agency, $newPlan);
        $changeAt  = $current->expires_at ?? now()->addDays(30);

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
                    $this->subscribe($agency, $newPlanSlug, $newPeriod, 'prepaid');

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
        $subscription = $this->activeSubscription($agency);
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

    // =========================================================================
    // Отмена и продление
    // =========================================================================

    public function cancelSubscription(Agency $agency, ?string $actorId = null): bool
    {
        $subscription = $this->activeSubscription($agency);
        if (! $subscription) return false;

        $subscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew'   => false,
        ]);

        BillingEvent::log('subscription.cancelled', $agency->id, $actorId, 'agency_subscription', $subscription->id);

        return true;
    }

    /**
     * Автопродление (вызывается из scheduler).
     */
    public function renewSubscription(AgencySubscription $subscription): bool
    {
        if (! $subscription->auto_renew) return false;

        $agency = $subscription->agency;
        $plan   = $subscription->plan;

        $durationDays = $subscription->billing_period === 'yearly' ? 365 : 30;
        $expiresAt    = now()->addDays($durationDays);

        $subscription->update([
            'status'       => 'active',
            'starts_at'    => now(),
            'expires_at'   => $expiresAt,
            'grace_ends_at' => $expiresAt->copy()->addDays($plan->grace_period_days),
        ]);

        $agency->update(['plan_expires_at' => $expiresAt]);

        if ($subscription->payment_model === 'prepaid') {
            $this->createSubscriptionInvoice($subscription, $plan, $subscription->discount_amount);
        }

        BillingEvent::log('subscription.renewed', $agency->id, null, 'agency_subscription', $subscription->id);

        return true;
    }

    // =========================================================================
    // Лимиты
    // =========================================================================

    public function checkLimits(Agency $agency): array
    {
        $planValue = $agency->plan instanceof \BackedEnum ? $agency->plan->value : (string) $agency->plan;
        $plan      = BillingPlan::find($planValue);

        if (! $plan) {
            return ['valid' => false, 'reason' => 'Plan not found'];
        }

        // Аддоны
        $extraManagers = 0;
        $extraCases    = 0;
        $extraLeads    = 0;

        $activeAddons = AgencyAddon::where('agency_id', $agency->id)->where('status', 'active')->get();
        foreach ($activeAddons as $aa) {
            $limits = $aa->addon?->limits ?? [];
            $extraManagers += $limits['extra_managers'] ?? 0;
            $extraCases    += $limits['extra_cases'] ?? 0;
            $extraLeads    += $limits['extra_leads'] ?? 0;
        }

        $maxManagers = $plan->max_managers === 0 ? null : $plan->max_managers + $extraManagers;
        $maxCases    = $plan->max_cases === 0 ? null : $plan->max_cases + $extraCases;
        $maxLeads    = $plan->max_leads_per_month === 0 ? null : $plan->max_leads_per_month + $extraLeads;

        $managersCount = $agency->users()->whereIn('role', ['owner', 'manager'])->count();
        $casesCount    = \App\Modules\Case\Models\VisaCase::where('agency_id', $agency->id)
            ->whereNotIn('stage', ['result'])
            ->count();

        // Лиды за текущий месяц
        $leadsThisMonth = DB::table('public_leads')
            ->where('assigned_agency_id', $agency->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'valid'           => true,
            'managers_count'  => $managersCount,
            'max_managers'    => $maxManagers,
            'cases_count'     => $casesCount,
            'max_cases'       => $maxCases,
            'leads_count'     => $leadsThisMonth,
            'max_leads'       => $maxLeads,
            'has_marketplace' => $plan->has_marketplace,
            'has_api_access'  => $plan->has_api_access,
            'can_add_manager' => $maxManagers === null || $managersCount < $maxManagers,
            'can_add_case'    => $maxCases === null || $casesCount < $maxCases,
            'can_accept_lead' => $maxLeads === null || $leadsThisMonth < $maxLeads,
        ];
    }

    // =========================================================================
    // Dunning (повторные списания)
    // =========================================================================

    /**
     * Обработка просроченных подписок (вызывается из scheduler).
     */
    public function processExpiredSubscriptions(): int
    {
        $count = 0;

        // Подписки, у которых истёк срок и grace-period
        $expired = AgencySubscription::whereIn('status', ['active', 'trialing', 'past_due'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where(function ($q) {
                $q->whereNull('grace_ends_at')->orWhere('grace_ends_at', '<', now());
            })
            ->get();

        foreach ($expired as $sub) {
            if ($sub->auto_renew && $sub->payment_model === 'earn_first') {
                // Earn-first: автопродление без оплаты
                $this->renewSubscription($sub);
            } else {
                $sub->update(['status' => 'expired']);
                $sub->agency?->update(['plan_expires_at' => now()]);
                BillingEvent::log('subscription.expired', $sub->agency_id, null, 'agency_subscription', $sub->id);
            }
            $count++;
        }

        // Подписки в grace-period — отметить past_due
        AgencySubscription::whereIn('status', ['active', 'trialing'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '>', now())
            ->update(['status' => 'past_due']);

        return $count;
    }

    // =========================================================================
    // Вспомогательные
    // =========================================================================

    public function activeSubscription(Agency $agency): ?AgencySubscription
    {
        return AgencySubscription::where('agency_id', $agency->id)
            ->active()
            ->latest('starts_at')
            ->first();
    }

    public function getWallet(Agency $agency): AgencyWallet
    {
        return AgencyWallet::firstOrCreate(
            ['agency_id' => $agency->id],
            ['balance' => 0, 'total_earned' => 0, 'total_deducted' => 0, 'total_paid_out' => 0, 'pending_payout' => 0]
        );
    }

    private function cancelCurrentSubscription(Agency $agency): void
    {
        AgencySubscription::where('agency_id', $agency->id)
            ->active()
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    private function hasHadSubscription(Agency $agency): bool
    {
        return AgencySubscription::where('agency_id', $agency->id)->exists();
    }

    public function calculateVat(int $amount): array
    {
        $enabled = PlatformSetting::get('vat_enabled', false);
        if (! $enabled) {
            return ['vat' => 0, 'rate' => 0];
        }

        $rate = (float) PlatformSetting::get('vat_rate', 12);
        $vat  = (int) round($amount * $rate / 100);

        return ['vat' => $vat, 'rate' => $rate];
    }

    private function createSubscriptionInvoice(AgencySubscription $subscription, BillingPlan $plan, int $discount): Invoice
    {
        $price = $subscription->billing_period === 'yearly' ? $plan->price_yearly : $plan->price_uzs;
        $subtotal = max(0, $price - $discount);
        $vatInfo  = $this->calculateVat($subtotal);

        return Invoice::create([
            'agency_id'       => $subscription->agency_id,
            'subscription_id' => $subscription->id,
            'number'          => Invoice::generateNumber(),
            'type'            => 'subscription',
            'status'          => 'issued',
            'subtotal'        => $subtotal,
            'vat_amount'      => $vatInfo['vat'],
            'discount_amount' => $discount,
            'total'           => $subtotal + $vatInfo['vat'],
            'currency'        => 'UZS',
            'line_items'      => [[
                'description' => "{$plan->name} — {$subscription->billing_period}",
                'quantity'    => 1,
                'unit_price'  => $price,
                'discount'    => $discount,
                'total'       => $subtotal,
            ]],
            'issued_at'       => now(),
            'due_at'          => now()->addDays(7),
        ]);
    }
}
