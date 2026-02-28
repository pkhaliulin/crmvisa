<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Все активные тарифы для публичной страницы
     */
    public function getPlans(): \Illuminate\Database\Eloquent\Collection
    {
        return BillingPlan::where('is_active', true)
                          ->orderBy('sort_order')
                          ->get();
    }

    /**
     * Текущая активная подписка агентства
     */
    public function currentSubscription(Agency $agency): ?AgencySubscription
    {
        return AgencySubscription::where('agency_id', $agency->id)
                                 ->active()
                                 ->latest('starts_at')
                                 ->first();
    }

    /**
     * Ручная активация плана (для оператора VisaBor / тестов)
     */
    public function activateManual(
        Agency $agency,
        string $planSlug,
        string $period,
        int    $durationDays
    ): AgencySubscription {
        $plan = BillingPlan::findOrFail($planSlug);

        return DB::transaction(function () use ($agency, $plan, $period, $durationDays) {
            // Отменяем текущую активную подписку
            AgencySubscription::where('agency_id', $agency->id)
                              ->active()
                              ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $subscription = AgencySubscription::create([
                'agency_id'      => $agency->id,
                'plan_slug'      => $plan->slug,
                'status'         => 'active',
                'billing_period' => $period,
                'payment_method' => 'manual',
                'starts_at'      => now(),
                'expires_at'     => now()->addDays($durationDays),
            ]);

            // Синхронизируем план агентства
            $agency->update([
                'plan'            => $plan->slug,
                'plan_expires_at' => $subscription->expires_at,
            ]);

            return $subscription;
        });
    }

    /**
     * Отмена подписки
     */
    public function cancel(Agency $agency): bool
    {
        $subscription = $this->currentSubscription($agency);

        if (! $subscription) {
            return false;
        }

        $subscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return true;
    }

    /**
     * История платежей агентства
     */
    public function transactions(Agency $agency, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return PaymentTransaction::where('agency_id', $agency->id)
                                 ->latest()
                                 ->paginate($perPage);
    }

    /**
     * Создать транзакцию вручную (для записи Payme / ручных платежей)
     */
    public function recordTransaction(
        Agency $agency,
        string $subscriptionId,
        string $provider,
        int    $amount,
        string $currency,
        string $providerTxId,
        string $description = ''
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'agency_id'               => $agency->id,
            'subscription_id'         => $subscriptionId,
            'provider'                => $provider,
            'provider_transaction_id' => $providerTxId,
            'amount'                  => $amount,
            'currency'                => $currency,
            'status'                  => 'succeeded',
            'description'             => $description,
            'paid_at'                 => now(),
        ]);
    }

    /**
     * Проверить лимиты тарифа
     */
    public function checkPlanLimits(Agency $agency): array
    {
        $plan = BillingPlan::find($agency->plan);

        if (! $plan) {
            return ['valid' => false, 'reason' => 'Plan not found'];
        }

        $managersCount = $agency->users()->whereIn('role', ['owner', 'manager'])->count();
        $casesCount    = $agency->hasMany(\App\Modules\Case\Models\VisaCase::class, 'agency_id')
                                ->whereNotIn('stage', ['result'])
                                ->count();

        $maxManagers = $plan->max_managers === 0 ? PHP_INT_MAX : $plan->max_managers;
        $maxCases    = $plan->max_cases === 0    ? PHP_INT_MAX : $plan->max_cases;

        return [
            'valid'          => true,
            'managers'       => ['current' => $managersCount, 'max' => $plan->max_managers, 'exceeded' => $managersCount >= $maxManagers],
            'cases'          => ['current' => $casesCount,    'max' => $plan->max_cases,    'exceeded' => $casesCount >= $maxCases],
            'has_marketplace' => $plan->has_marketplace,
        ];
    }
}
