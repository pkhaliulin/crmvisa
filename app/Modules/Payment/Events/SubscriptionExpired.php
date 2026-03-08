<?php

namespace App\Modules\Payment\Events;

use App\Modules\Payment\Models\AgencySubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AgencySubscription $subscription,
        public readonly string $agencyId,
    ) {}
}
