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
                'activation_fee_paid'        => $plan->activation_fee_uzs === 0,
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
            ->where('agency_id', $agency->id)
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
