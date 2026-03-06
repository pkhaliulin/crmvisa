<?php

namespace App\Modules\Payment\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Payment\Events\PaymentReceived;
use App\Modules\Payment\Models\AgencySubscription;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\Payment\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function __construct(private readonly BillingEngine $engine) {}

    public function getPlans(): \Illuminate\Database\Eloquent\Collection
    {
        return BillingPlan::where('is_active', true)
                          ->where('is_public', true)
                          ->orderBy('sort_order')
                          ->get();
    }

    public function currentSubscription(Agency $agency): ?AgencySubscription
    {
        return $this->engine->activeSubscription($agency);
    }

    public function activateManual(
        Agency $agency,
        string $planSlug,
        string $period,
        int    $durationDays
    ): AgencySubscription {
        return $this->engine->subscribe(
            $agency,
            $planSlug,
            $period,
            'prepaid',
        );
    }

    public function cancel(Agency $agency): bool
    {
        return $this->engine->cancelSubscription($agency);
    }

    public function transactions(Agency $agency, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return PaymentTransaction::where('agency_id', $agency->id)
                                 ->latest()
                                 ->paginate($perPage);
    }

    public function recordTransaction(
        Agency $agency,
        string $subscriptionId,
        string $provider,
        int    $amount,
        string $currency,
        string $providerTxId,
        string $description = ''
    ): PaymentTransaction {
        $transaction = PaymentTransaction::create([
            'agency_id'               => $agency->id,
            'subscription_id'         => $subscriptionId,
            'type'                    => 'subscription',
            'direction'               => 'inbound',
            'provider'                => $provider,
            'provider_transaction_id' => $providerTxId,
            'amount'                  => $amount,
            'currency'                => $currency,
            'status'                  => 'succeeded',
            'description'             => $description,
            'paid_at'                 => now(),
        ]);

        PaymentReceived::dispatch($transaction, $agency->id);

        return $transaction;
    }

    public function checkPlanLimits(Agency $agency): array
    {
        return $this->engine->checkLimits($agency);
    }
}
