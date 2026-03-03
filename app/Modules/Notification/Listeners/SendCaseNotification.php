<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Case\Events\CaseCreated;
use App\Modules\Case\Events\CaseStatusChanged;
use Illuminate\Support\Facades\Log;

class SendCaseNotification
{
    public function handleCreated(CaseCreated $event): void
    {
        Log::channel('single')->info('Domain Event: CaseCreated', [
            'case_id'    => $event->case->id,
            'agency_id'  => $event->case->agency_id,
            'client_id'  => $event->case->client_id,
            'created_by' => $event->createdBy,
        ]);
    }

    public function handleStatusChanged(CaseStatusChanged $event): void
    {
        Log::channel('single')->info('Domain Event: CaseStatusChanged', [
            'case_id'        => $event->case->id,
            'agency_id'      => $event->case->agency_id,
            'previous_stage' => $event->previousStage,
            'new_stage'      => $event->newStage,
            'changed_by'     => $event->changedBy,
        ]);
    }
}
