<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Models\AgencyWallet;
use App\Modules\Payment\Models\BillingEvent;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\LedgerEntry;
use App\Modules\Payment\Models\PaymentTransaction;
use App\Modules\Payment\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;

class PaymentProcessingService
{
    public function __construct(
        private readonly SubscriptionService  $subscriptionService,
        private readonly BillingHelperService $helper,
    ) {}

    /**
     * Оплата активационного сбора.
     */
    public function payActivationFee(
        Agency  $agency,
        string  $provider = 'manual',
        ?string $providerTxId = null,
    ): PaymentTransaction {
        $subscription = $this->subscriptionService->activeSubscription($agency);
        if (! $subscription) {
            throw new \RuntimeException('Нет активной подписки');
        }

        $plan = $subscription->plan;
        if ($plan->activation_fee_uzs <= 0 || $subscription->activation_fee_paid) {
            throw new \RuntimeException('Активационный сбор не требуется или уже оплачен');
        }

        return DB::transaction(function () use ($agency, $subscription, $plan, $provider, $providerTxId) {
            $vatInfo = $this->helper->calculateVat($plan->activation_fee_uzs);

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
        $subscription = $this->subscriptionService->activeSubscription($agency);
        if (! $subscription) {
            throw new \RuntimeException('Нет активной подписки');
        }

        $plan   = $subscription->plan;
        $price  = $subscription->billing_period === 'yearly' ? $plan->price_yearly : $plan->price_uzs;
        $amount = max(0, $price - $subscription->discount_amount);

        return DB::transaction(function () use ($agency, $subscription, $amount, $provider, $providerTxId) {
            $vatInfo = $this->helper->calculateVat($amount);

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

    /**
     * Earn-first удержание: вызывается после завершения заказа.
     */
    public function deductEarnFirst(Agency $agency, int $orderAmount, string $orderId): ?PaymentTransaction
    {
        $subscription = $this->subscriptionService->activeSubscription($agency);
        if (! $subscription || ! $subscription->isEarnFirst()) return null;

        $remaining = $subscription->earn_first_target - $subscription->earn_first_deducted_total;
        if ($remaining <= 0) return null;

        $deductionPct = $subscription->plan->earn_first_deduction_pct;
        $deduction    = (int) round($orderAmount * $deductionPct / 100);
        $deduction    = min($deduction, $remaining);

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
                'deduction'       => $deduction,
                'total_deducted'  => $subscription->earn_first_deducted_total,
                'target'          => $subscription->earn_first_target,
            ]);

            return $transaction;
        });
    }
}
