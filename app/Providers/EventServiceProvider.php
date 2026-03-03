<?php

namespace App\Providers;

use App\Modules\Case\Events\CaseCreated;
use App\Modules\Case\Events\CaseStatusChanged;
use App\Modules\Client\Events\ClientRegistered;
use App\Modules\Notification\Listeners\LogBusinessEvent;
use App\Modules\Notification\Listeners\SendCaseNotification;
use App\Modules\Payment\Events\PaymentReceived;
use App\Modules\Scoring\Events\ScoringCalculated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CaseCreated::class => [
            [SendCaseNotification::class, 'handleCreated'],
        ],
        CaseStatusChanged::class => [
            [SendCaseNotification::class, 'handleStatusChanged'],
        ],
        ClientRegistered::class => [
            [LogBusinessEvent::class, 'handleClientRegistered'],
        ],
        PaymentReceived::class => [
            [LogBusinessEvent::class, 'handlePaymentReceived'],
        ],
        ScoringCalculated::class => [
            [LogBusinessEvent::class, 'handleScoringCalculated'],
        ],
    ];
}
