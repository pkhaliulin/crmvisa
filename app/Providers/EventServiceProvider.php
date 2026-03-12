<?php

namespace App\Providers;

use App\Modules\Case\Events\CaseAssigned;
use App\Modules\Case\Events\CaseCreated;
use App\Modules\Case\Events\CaseStatusChanged;
use App\Modules\Case\Events\SlaViolation;
use App\Modules\Client\Events\ClientCreatedViaPortal;
use App\Modules\Client\Events\ClientRegistered;
use App\Modules\Document\Events\DocumentUploaded;
use App\Modules\Case\Listeners\InvalidateAgencyCache;
use App\Modules\Case\Listeners\LogCaseActivity;
use App\Modules\Notification\Listeners\LogBusinessEvent;
use App\Modules\Notification\Listeners\SendCaseNotification;
use App\Modules\Payment\Events\PaymentReceived;
use App\Modules\Payment\Events\SubscriptionChanged;
use App\Modules\Payment\Events\SubscriptionExpired;
use App\Modules\LeadGen\Events\LeadIncoming;
use App\Modules\Notification\Listeners\SendLeadNotification;
use App\Modules\Scoring\Events\ScoringCalculated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CaseCreated::class => [
            [SendCaseNotification::class, 'handleCreated'],
            [InvalidateAgencyCache::class, 'handleCaseCreated'],
        ],
        CaseStatusChanged::class => [
            [SendCaseNotification::class, 'handleStatusChanged'],
            [InvalidateAgencyCache::class, 'handleCaseStatusChanged'],
        ],
        CaseAssigned::class => [
            [SendCaseNotification::class, 'handleCaseAssigned'],
        ],
        SlaViolation::class => [
            [SendCaseNotification::class, 'handleSlaViolation'],
        ],
        ClientRegistered::class => [
            [LogBusinessEvent::class, 'handleClientRegistered'],
            [InvalidateAgencyCache::class, 'handleClientRegistered'],
        ],
        ClientCreatedViaPortal::class => [
            [LogBusinessEvent::class, 'handleClientCreatedViaPortal'],
        ],
        DocumentUploaded::class => [
            [LogBusinessEvent::class, 'handleDocumentUploaded'],
            [LogCaseActivity::class, 'handleDocumentUploaded'],
        ],
        PaymentReceived::class => [
            [LogBusinessEvent::class, 'handlePaymentReceived'],
            [InvalidateAgencyCache::class, 'handlePaymentReceived'],
            [LogCaseActivity::class, 'handlePaymentReceived'],
        ],
        SubscriptionChanged::class => [
            [LogBusinessEvent::class, 'handleSubscriptionChanged'],
        ],
        SubscriptionExpired::class => [
            [LogBusinessEvent::class, 'handleSubscriptionExpired'],
        ],
        ScoringCalculated::class => [
            [LogBusinessEvent::class, 'handleScoringCalculated'],
        ],
        LeadIncoming::class => [
            [SendLeadNotification::class, 'handle'],
        ],
    ];
}
