<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Case\Events\CaseAssigned;
use App\Modules\Case\Events\CaseCreated;
use App\Modules\Case\Events\CaseStatusChanged;
use App\Modules\Case\Events\SlaViolation;
use App\Modules\User\Models\User;
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

        try {
            activity()
                ->performedOn($event->case)
                ->causedBy(User::find($event->createdBy))
                ->withProperties(['stage' => $event->case->stage])
                ->log('Заявка создана');
        } catch (\Throwable $e) {
            Log::channel('single')->debug('Activity log unavailable for CaseCreated', [
                'error' => $e->getMessage(),
            ]);
        }
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

        try {
            activity()
                ->performedOn($event->case)
                ->causedBy($event->changedBy ? User::find($event->changedBy) : null)
                ->withProperties([
                    'previous_stage' => $event->previousStage,
                    'new_stage'      => $event->newStage,
                ])
                ->log("Статус изменён: {$event->previousStage} -> {$event->newStage}");
        } catch (\Throwable $e) {
            Log::channel('single')->debug('Activity log unavailable for CaseStatusChanged', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function handleCaseAssigned(CaseAssigned $event): void
    {
        Log::channel('single')->info('Domain Event: CaseAssigned', [
            'case_id'     => $event->case->id,
            'agency_id'   => $event->case->agency_id,
            'assigned_to' => $event->assignedTo,
            'assigned_by' => $event->assignedBy,
        ]);

        // TODO: интеграция SMS/Telegram уведомления назначенному сотруднику
    }

    public function handleSlaViolation(SlaViolation $event): void
    {
        Log::channel('single')->warning('Domain Event: SlaViolation', [
            'case_id'      => $event->case->id,
            'agency_id'    => $event->case->agency_id,
            'stage'        => $event->stage,
            'due_at'       => $event->dueAt->format('Y-m-d H:i:s'),
            'overdue_days' => $event->overdueDays,
        ]);

        // TODO: интеграция SMS/Telegram уведомления о нарушении SLA
    }
}
