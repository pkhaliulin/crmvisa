<?php

namespace App\Modules\Payment\Events;

use App\Modules\Payment\Models\PaymentTransaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly PaymentTransaction $transaction,
        public readonly string $agencyId,
    ) {}
}
