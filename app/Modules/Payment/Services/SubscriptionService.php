<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\AgencyWallet;
use App\Modules\Payment\Models\BillingEvent;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\Coupon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        private readonly BillingHelperService $helper,
    ) {}

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

            $expiresAt   = now()->addDays($durationDays);
            $graceEndsAt = $expiresAt->copy()->addDays($plan->grace_period_days);

            // Earn-first: целевая сумма подписки
            $earnFirstTarget = 0;
            if ($paymentModel === 'earn_first' && $plan->earn_first_enabled) {
                $basePrice       = $billingPeriod === 'yearly' ? $plan->price_yearly : $plan->price_uzs;
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
                'activation_fee_paid'        => true,
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
                $this->helper->createSubscriptionInvoice($subscription, $plan, $discount);
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
            'status'        => 'active',
            'starts_at'     => now(),
            'expires_at'    => $expiresAt,
            'grace_ends_at' => $expiresAt->copy()->addDays($plan->grace_period_days),
        ]);

        $agency->update(['plan_expires_at' => $expiresAt]);

        if ($subscription->payment_model === 'prepaid') {
            $this->helper->createSubscriptionInvoice($subscription, $plan, $subscription->discount_amount);
        }

        BillingEvent::log('subscription.renewed', $agency->id, null, 'agency_subscription', $subscription->id);

        return true;
    }

    public function activeSubscription(Agency $agency): ?AgencySubscription
    {
        return AgencySubscription::where('agency_id', $agency->id)
            ->active()
            ->latest('starts_at')
            ->first();
    }

    public function cancelCurrentSubscription(Agency $agency): void
    {
        AgencySubscription::where('agency_id', $agency->id)
            ->active()
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    public function hasHadSubscription(Agency $agency): bool
    {
        return AgencySubscription::where('agency_id', $agency->id)->exists();
    }
}
