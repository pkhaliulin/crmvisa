<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Events\CaseAssigned;
use App\Modules\Case\Models\VisaCase;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseBulkController extends Controller
{
    /**
     * POST /cases/bulk/assign — массовое назначение менеджера.
     * Owner: может назначить любого менеджера.
     * Manager: может назначить только себя.
     */
    public function assign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'case_ids'   => ['required', 'array', 'min:1', 'max:100'],
            'case_ids.*' => ['required', 'uuid'],
            'manager_id' => ['required', 'uuid'],
        ]);

        $user = $request->user();

        // Manager может назначить только себя
        if ($user->role === 'manager' && $data['manager_id'] !== $user->id) {
            return ApiResponse::forbidden('Менеджер может назначить только себя.');
        }

        // Проверяем что manager_id — реальный менеджер/owner этого агентства
        $managerExists = \App\Modules\User\Models\User::where('id', $data['manager_id'])
            ->where('agency_id', $user->agency_id)
            ->whereIn('role', ['manager', 'owner'])
            ->where('is_active', true)
            ->exists();

        if (! $managerExists) {
            return ApiResponse::error('Менеджер не найден.', null, 404);
        }

        $cases = VisaCase::where('agency_id', $user->agency_id)
            ->whereIn('id', $data['case_ids'])
            ->get();

        $updated = 0;
        foreach ($cases as $case) {
            // Проверяем право: owner может любые, manager — только свои (или незакреплённые)
            if ($user->role === 'manager' && $case->assigned_to && $case->assigned_to !== $user->id) {
                continue;
            }

            $wasUnassigned = ! $case->assigned_to;
            $case->update(['assigned_to' => $data['manager_id']]);

            if ($wasUnassigned) {
                CaseAssigned::dispatch($case->fresh(), $data['manager_id'], $user->id);
            }

            $updated++;
        }

        return ApiResponse::success(['updated' => $updated]);
    }

    /**
     * POST /cases/bulk/priority — массовое изменение приоритета.
     */
    public function priority(Request $request): JsonResponse
    {
        $data = $request->validate([
            'case_ids'   => ['required', 'array', 'min:1', 'max:100'],
            'case_ids.*' => ['required', 'uuid'],
            'priority'   => ['required', 'string', 'in:low,normal,high,urgent'],
        ]);

        $user = $request->user();

        $query = VisaCase::where('agency_id', $user->agency_id)
            ->whereIn('id', $data['case_ids']);

        // Manager видит только свои
        if ($user->role === 'manager') {
            $agency = $user->agency;
            if (! $agency?->managers_see_all_cases) {
                $query->where('assigned_to', $user->id);
            }
        }

        $updated = $query->update(['priority' => $data['priority']]);

        return ApiResponse::success(['updated' => $updated]);
    }

    /**
     * POST /cases/bulk/export — CSV экспорт выбранных заявок.
     */
    public function export(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'case_ids'   => ['required', 'array', 'min:1', 'max:500'],
            'case_ids.*' => ['required', 'uuid'],
        ]);

        $user = $request->user();

        $query = VisaCase::where('agency_id', $user->agency_id)
            ->whereIn('id', $data['case_ids'])
            ->with(['client:id,name,phone', 'assignee:id,name']);

        if ($user->role === 'manager') {
            $agency = $user->agency;
            if (! $agency?->managers_see_all_cases) {
                $query->where('assigned_to', $user->id);
            }
        }

        $cases = $query->get();

        return response()->streamDownload(function () use ($cases) {
            $out = fopen('php://output', 'w');

            // BOM for Excel
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [
                'Case Number',
                'Client',
                'Country',
                'Visa Type',
                'Stage',
                'Priority',
                'Manager',
                'Critical Date',
                'Travel Date',
                'Created At',
            ], ';');

            foreach ($cases as $c) {
                fputcsv($out, [
                    $c->case_number,
                    $c->client?->name ?? '',
                    $c->country_code,
                    $c->visa_type,
                    $c->stage,
                    $c->priority,
                    $c->assignee?->name ?? '',
                    $c->critical_date?->format('d.m.Y') ?? '',
                    $c->travel_date?->format('d.m.Y') ?? '',
                    $c->created_at?->format('d.m.Y H:i') ?? '',
                ], ';');
            }

            fclose($out);
        }, 'cases-export-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
