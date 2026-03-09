<?php

namespace App\Modules\Notification\Listeners;

use App\Modules\Case\Events\CaseAssigned;
use App\Modules\Case\Events\CaseCreated;
use App\Modules\Case\Events\CaseStatusChanged;
use App\Modules\Case\Events\SlaViolation;
use App\Modules\Notification\Notifications\BusinessNotification;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;

class SendCaseNotification
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

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

        $clientName = $event->case->client?->name ?? 'Неизвестный';

        $this->notificationService->dispatch(
            $event->case->agency_id,
            'case.created',
            new BusinessNotification('case.created', [
                'case_id'      => $event->case->id,
                'case_number'  => $event->case->case_number,
                'client_name'  => $clientName,
                'country_code' => $event->case->country_code,
                'visa_type'    => $event->case->visa_type,
                'stage'        => $event->case->stage,
                'created_by'   => $event->createdBy,
                'message'      => "Новая заявка: {$clientName} ({$event->case->country_code})",
                'sms'          => "Новая заявка: {$clientName}, {$event->case->country_code}. #{$event->case->case_number}",
            ]),
            ['case' => $event->case],
        );
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

        $stageLabel    = config("stages.{$event->newStage}.label", $event->newStage);
        $prevLabel     = config("stages.{$event->previousStage}.label", $event->previousStage);
        $clientName    = $event->case->client?->name ?? 'Неизвестный';

        // Определяем тип события на основе public_status
        $eventType = 'case.status_changed';
        $publicStatus = $event->case->public_status;
        if ($publicStatus === 'completed') {
            $eventType = 'case.completed';
        } elseif ($publicStatus === 'rejected') {
            $eventType = 'case.rejected';
        } elseif ($publicStatus === 'cancelled') {
            $eventType = 'case.cancelled';
        }

        $this->notificationService->dispatch(
            $event->case->agency_id,
            $eventType,
            new BusinessNotification($eventType, [
                'case_id'        => $event->case->id,
                'case_number'    => $event->case->case_number,
                'client_name'    => $clientName,
                'previous_stage' => $event->previousStage,
                'new_stage'      => $event->newStage,
                'changed_by'     => $event->changedBy,
                'message'        => "Заявка {$clientName}: {$prevLabel} -> {$stageLabel}",
                'details'        => [
                    "Страна: {$event->case->country_code}",
                    "Заявка: #{$event->case->case_number}",
                ],
                'sms' => "Заявка #{$event->case->case_number}: {$prevLabel} -> {$stageLabel}",
            ]),
            ['case' => $event->case],
        );
    }

    public function handleCaseAssigned(CaseAssigned $event): void
    {
        Log::channel('single')->info('Domain Event: CaseAssigned', [
            'case_id'     => $event->case->id,
            'agency_id'   => $event->case->agency_id,
            'assigned_to' => $event->assignedTo,
            'assigned_by' => $event->assignedBy,
        ]);

        try {
            activity()
                ->performedOn($event->case)
                ->causedBy($event->assignedBy ? User::find($event->assignedBy) : null)
                ->withProperties([
                    'assigned_to' => $event->assignedTo,
                ])
                ->log('Менеджер назначен');
        } catch (\Throwable $e) {
            Log::channel('single')->debug('Activity log unavailable for CaseAssigned', [
                'error' => $e->getMessage(),
            ]);
        }

        $clientName  = $event->case->client?->name ?? 'Неизвестный';
        $manager     = User::find($event->assignedTo);
        $managerName = $manager?->name ?? 'Менеджер';

        $this->notificationService->dispatch(
            $event->case->agency_id,
            'case.assigned',
            new BusinessNotification('case.assigned', [
                'case_id'      => $event->case->id,
                'case_number'  => $event->case->case_number,
                'client_name'  => $clientName,
                'assigned_to'  => $event->assignedTo,
                'assigned_by'  => $event->assignedBy,
                'manager_name' => $managerName,
                'message'      => "Заявка {$clientName} назначена на {$managerName}",
                'details'      => [
                    "Страна: {$event->case->country_code}",
                    "Заявка: #{$event->case->case_number}",
                ],
                'sms' => "Вам назначена заявка #{$event->case->case_number}: {$clientName}",
            ]),
            ['case' => $event->case, 'assigned_to' => $event->assignedTo],
        );
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

        $clientName = $event->case->client?->name ?? 'Неизвестный';
        $stageLabel = config("stages.{$event->stage}.label", $event->stage);

        $this->notificationService->dispatch(
            $event->case->agency_id,
            'sla.violation',
            new BusinessNotification('sla.violation', [
                'case_id'      => $event->case->id,
                'case_number'  => $event->case->case_number,
                'client_name'  => $clientName,
                'stage'        => $event->stage,
                'due_at'       => $event->dueAt->format('Y-m-d H:i:s'),
                'overdue_days' => $event->overdueDays,
                'subject'      => "SLA нарушен: {$clientName} — просрочка {$event->overdueDays} дн.",
                'message'      => "Заявка #{$event->case->case_number} ({$clientName}): этап «{$stageLabel}» просрочен на {$event->overdueDays} дн.",
                'details'      => [
                    "Дедлайн: {$event->dueAt->format('d.m.Y H:i')}",
                    "Страна: {$event->case->country_code}",
                ],
                'sms' => "SLA! Заявка #{$event->case->case_number} просрочена на {$event->overdueDays} дн.",
            ]),
            ['case' => $event->case],
        );
    }
}
