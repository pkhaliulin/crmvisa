<?php

namespace App\Modules\Case\Listeners;

use App\Modules\Case\Events\CaseCreated;
use App\Modules\Case\Events\CaseStatusChanged;
use App\Modules\Client\Events\ClientRegistered;
use App\Modules\Payment\Events\PaymentReceived;
use App\Support\Services\AgencyCacheService;

class InvalidateAgencyCache
{
    public function handleCaseCreated(CaseCreated $event): void
    {
        AgencyCacheService::invalidateDashboard($event->case->agency_id);
    }

    public function handleCaseStatusChanged(CaseStatusChanged $event): void
    {
        AgencyCacheService::invalidateDashboard($event->case->agency_id);
    }

    public function handleClientRegistered(ClientRegistered $event): void
    {
        AgencyCacheService::invalidateDashboard($event->agencyId);
    }

    public function handlePaymentReceived(PaymentReceived $event): void
    {
        AgencyCacheService::invalidateFinancial($event->agencyId);
        AgencyCacheService::invalidateDashboard($event->agencyId);
    }
}
