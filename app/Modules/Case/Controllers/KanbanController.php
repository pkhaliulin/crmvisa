<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class KanbanController extends Controller
{
    public function board(Request $request): JsonResponse
    {
        $user     = $request->user();
        $agencyId = $user->agency_id;
        $isOwner  = in_array($user->role, ['owner', 'superadmin']);

        $query = VisaCase::where('cases.agency_id', $agencyId)
            ->with([
                'client:id,name,phone,nationality',
                'assignee:id,name',
                'stageHistory' => fn ($q) => $q->whereNull('exited_at'),
            ]);

        // Исключаем черновики и отменённые — они точно не на канбане
        $query->where(function ($q) {
            $q->whereNotIn('public_status', ['draft', 'cancelled'])
              ->orWhereNull('public_status');
        });

        // Менеджер видит только свои заявки
        if (! $isOwner) {
            $query->where('assigned_to', $user->id);
        }

        $cases = $query->get();

        // Строим доску из config/stages.php
        $stages = collect(config('stages'))->sortBy('order');

        $board = $stages->map(function (array $stage) use ($cases) {
            $stageCases = $cases
                ->where('stage', $stage['key'])
                ->map(fn ($case) => $this->formatCard($case))
                ->values();

            return [
                'key'        => $stage['key'],
                'label'      => $stage['label'],
                'order'      => $stage['order'],
                'client_msg' => $stage['client_msg'],
                'sla_hours'  => $stage['sla_hours'] ?? null,
                'tooltip'    => $stage['tooltip'] ?? '',
                'count'      => $stageCases->count(),
                'cases'      => $stageCases,
            ];
        })->values();

        return ApiResponse::success([
            'board'      => $board,
            'role'       => $user->role,
            'total'      => $cases->count(),
            'overdue'    => $cases->filter(fn ($c) => $this->isOverdue($c))->count(),
            'critical'   => $cases->filter(fn ($c) => $this->isCritical($c))->count(),
        ]);
    }

    // -------------------------------------------------------------------------

    private function formatCard(VisaCase $case): array
    {
        $isOverdue  = $this->isOverdue($case);
        $isCritical = $this->isCritical($case);

        $daysLeft = null;
        if ($case->critical_date) {
            $daysLeft = (int) Carbon::now()->diffInDays($case->critical_date, false);
        }

        // SLA данные из текущего CaseStage
        $currentStage    = $case->stageHistory->first();
        $stageSlaOverdue = false;
        $stageSlaHoursLeft = null;

        if ($currentStage && $currentStage->sla_due_at) {
            $stageSlaOverdue   = $currentStage->is_overdue || $currentStage->sla_due_at->isPast();
            $stageSlaHoursLeft = (int) Carbon::now()->diffInHours($currentStage->sla_due_at, false);
        }

        return [
            'id'                  => $case->id,
            'case_number'         => $case->case_number,
            'priority'            => $case->priority,
            'country_code'        => $case->country_code,
            'visa_type'           => $case->visa_type,
            'travel_date'         => $case->travel_date?->toDateString(),
            'critical_date'       => $case->critical_date?->toDateString(),
            'days_left'           => $daysLeft,
            'is_overdue'          => $isOverdue,
            'is_critical'         => $isCritical,
            'urgency'             => $isOverdue ? 'overdue' : ($isCritical ? 'critical' : 'normal'),
            'stage_sla_overdue'   => $stageSlaOverdue,
            'stage_sla_hours_left'=> $stageSlaHoursLeft,
            'public_status'       => $case->public_status,
            'payment_status'      => $case->payment_status,
            'appointment_date'    => $case->appointment_date?->toDateString(),
            'appointment_time'    => $case->appointment_time,
            'appointment_location'=> $case->appointment_location,
            'client' => [
                'id'          => $case->client?->id,
                'name'        => $case->client?->name,
                'phone'       => $case->client?->phone,
                'nationality' => $case->client?->nationality,
            ],
            'assignee' => $case->assignee ? [
                'id'   => $case->assignee->id,
                'name' => $case->assignee->name,
            ] : null,
            'deadline_info' => VisaCase::deadlineExplanation($case->country_code, $case->visa_type),
            'created_at' => $case->created_at->toDateString(),
        ];
    }

    private function isOverdue(VisaCase $case): bool
    {
        return $case->critical_date
            && $case->stage !== 'result'
            && $case->critical_date->isPast();
    }

    private function isCritical(VisaCase $case): bool
    {
        return $case->critical_date
            && $case->stage !== 'result'
            && ! $case->critical_date->isPast()
            && Carbon::now()->diffInDays($case->critical_date, false) <= 5;
    }
}
