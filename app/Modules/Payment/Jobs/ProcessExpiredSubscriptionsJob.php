<?php

namespace App\Modules\Payment\Jobs;

use App\Modules\Payment\Events\SubscriptionExpired;
use App\Modules\Payment\Services\BillingHelperService;
use App\Modules\Payment\Models\AgencySubscription;
use App\Support\Traits\HasTenantJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExpiredSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasTenantJob;

    public function handle(BillingHelperService $billingHelper): void
    {
        // Глобальный job — superadmin context
        $this->captureTenant(null);
        $this->setTenantContext();
        $expiredBefore = AgencySubscription::query()
            ->where('status', 'active')
            ->where('ends_at', '<', now())
            ->get();

        $processed = $billingHelper->processExpiredSubscriptions();

        foreach ($expiredBefore as $subscription) {
            SubscriptionExpired::dispatch($subscription, $subscription->agency_id);
        }

        Log::channel('single')->info('ProcessExpiredSubscriptionsJob completed', [
            'processed_count' => $processed,
        ]);
    }
}
