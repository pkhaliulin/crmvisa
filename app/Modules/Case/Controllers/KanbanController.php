<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
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
            ]);

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

        return [
            'id'           => $case->id,
            'priority'     => $case->priority,
            'country_code' => $case->country_code,
            'visa_type'    => $case->visa_type,
            'travel_date'  => $case->travel_date?->toDateString(),
            'critical_date'=> $case->critical_date?->toDateString(),
            'days_left'    => $daysLeft,
            'is_overdue'   => $isOverdue,
            'is_critical'  => $isCritical,
            'urgency'      => $isOverdue ? 'overdue' : ($isCritical ? 'critical' : 'normal'),
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
