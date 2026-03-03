<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Client\Events\ClientRegistered;
use App\Modules\Payment\Events\PaymentReceived;
use App\Modules\Scoring\Events\ScoringCalculated;
use Illuminate\Support\Facades\Log;

class LogBusinessEvent
{
    public function handleClientRegistered(ClientRegistered $event): void
    {
        Log::channel('single')->info('Domain Event: ClientRegistered', [
            'client_id' => $event->client->id,
            'agency_id' => $event->agencyId,
        ]);
    }

    public function handlePaymentReceived(PaymentReceived $event): void
    {
        Log::channel('single')->info('Domain Event: PaymentReceived', [
            'transaction_id' => $event->transaction->id,
            'agency_id'      => $event->agencyId,
            'amount'         => $event->transaction->amount,
            'currency'       => $event->transaction->currency,
        ]);
    }

    public function handleScoringCalculated(ScoringCalculated $event): void
    {
        Log::channel('single')->info('Domain Event: ScoringCalculated', [
            'client_id'    => $event->clientId,
            'country_code' => $event->countryCode,
            'score'        => $event->score,
            'risk_level'   => $event->riskLevel,
        ]);
    }
}
