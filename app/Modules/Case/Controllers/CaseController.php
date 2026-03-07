<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function __construct(protected CaseService $service) {}

    public function index(Request $request): JsonResponse
    {
        $user     = $request->user();
        $agencyId = $user->agency_id;

        $filters = $request->validate([
            'stage'        => ['nullable', 'string', 'in:' . implode(',', array_keys(config('stages')))],
            'assigned_to'  => ['nullable', 'string', 'max:50'],
            'priority'     => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'country_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
            'q'            => ['nullable', 'string', 'max:100'],
            'date_from'    => ['nullable', 'date'],
            'date_to'      => ['nullable', 'date'],
        ]);

        $query = VisaCase::where('cases.agency_id', $agencyId)
            ->whereNotIn('cases.public_status', ['draft', 'awaiting_payment', 'cancelled'])
            ->with(['client:id,name,phone', 'assignee:id,name'])
            ->selectRaw("
                cases.*,
                (SELECT COUNT(*) FROM case_checklist cc WHERE cc.case_id = cases.id AND cc.is_required = true) as docs_total,
                (SELECT COUNT(*) FROM case_checklist cc WHERE cc.case_id = cases.id AND cc.is_required = true AND cc.status IN ('uploaded','approved')) as docs_uploaded
            ");

        // Менеджеры видят только свои кейсы, если агентство не разрешило иное
        if ($user->role === 'manager') {
            $agency = $user->agency;
            if (! $agency?->managers_see_all_cases) {
                $query->where('cases.assigned_to', $user->id);
            }
        }

        // Фильтр по этапу
        if (!empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }

        // Фильтр по менеджеру
        if (!empty($filters['assigned_to'])) {
            if ($filters['assigned_to'] === 'unassigned') {
                $query->whereNull('cases.assigned_to');
            } else {
                $query->where('cases.assigned_to', $filters['assigned_to']);
            }
        }

        // Фильтр по приоритету
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Фильтр по стране
        if (!empty($filters['country_code'])) {
            $query->where('country_code', strtoupper($filters['country_code']));
        }

        // Поиск по имени клиента или номеру заявки
        if (!empty($filters['q'])) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']);
            $query->where(function ($sub) use ($search) {
                $sub->where('cases.case_number', 'ilike', '%' . $search . '%')
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('name', 'ilike', '%' . $search . '%');
                    });
            });
        }

        // Фильтр по дате создания
        if (!empty($filters['date_from'])) {
            $query->whereDate('cases.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('cases.created_at', '<=', $filters['date_to']);
        }

        // Сортировка: сначала просроченные, потом горящие, остальные по дате
        $query->orderByRaw("
            CASE
                WHEN critical_date < NOW() AND stage != 'result' THEN 0
                WHEN critical_date <= NOW() + INTERVAL '5 days' AND stage != 'result' THEN 1
                ELSE 2
            END ASC,
            critical_date ASC NULLS LAST,
            cases.created_at DESC
        ");

        return ApiResponse::paginated($query->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $data = $request->validate([
            'client_id'    => ['required', 'uuid', "exists:clients,id,agency_id,{$agencyId}"],
            'country_code' => ['required', 'string', 'size:2'],
            'visa_type'    => ['required', 'string', 'max:50', 'exists:portal_visa_types,slug'],
            'assigned_to'  => ['nullable', 'uuid', "exists:users,id,agency_id,{$agencyId}"],
            'priority'     => ['nullable', 'in:low,normal,high,urgent'],
            'critical_date'=> ['nullable', 'date'],
            'travel_date'  => ['nullable', 'date'],
            'notes'        => ['nullable', 'string'],
        ]);

        $case = $this->service->createCase($data);

        return ApiResponse::created($case);
    }

    public function show(string $id): JsonResponse
    {
        $case = $this->service->findOrFail($id);

        return ApiResponse::success(
            $case->load(['client', 'assignee', 'stageHistory'])
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $data = $request->validate([
            'assigned_to'  => ['sometimes', 'nullable', 'uuid', "exists:users,id,agency_id,{$agencyId}"],
            'priority'     => ['sometimes', 'in:low,normal,high,urgent'],
            'critical_date'=> ['sometimes', 'nullable', 'date'],
            'travel_date'  => ['sometimes', 'nullable', 'date'],
            'notes'                => ['sometimes', 'nullable', 'string'],
            'appointment_date'     => ['sometimes', 'nullable', 'date'],
            'appointment_time'     => ['sometimes', 'nullable', 'string', 'max:10'],
            'appointment_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'expected_result_date' => ['sometimes', 'nullable', 'date'],
            'return_date'          => ['sometimes', 'nullable', 'date'],
        ]);

        $case = $this->service->updateCase($id, $data);

        return ApiResponse::success($case);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->service->delete($id);

        return ApiResponse::success(null, 'Case deleted.');
    }

    public function moveStage(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'stage' => ['required', 'string', 'in:' . implode(',', array_keys(config('stages')))],
            'notes' => ['nullable', 'string'],
        ]);

        /** @var VisaCase */
        $case = $this->service->findOrFail($id);
        $case = $this->service->moveToStage($case, $data['stage'], $data['notes'] ?? null);

        return ApiResponse::success($case);
    }

    public function critical(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->critical($request->user()->agency_id));
    }

    /**
     * POST /cases/{id}/complete — Завершить заявку (одобрено/отказ).
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'result_type'            => ['required', 'in:approved,rejected'],
            'result_notes'           => ['nullable', 'string'],
            'visa_issued_at'         => ['nullable', 'date'],
            'visa_received_at'       => ['nullable', 'date'],
            'visa_validity'          => ['nullable', 'string', 'max:100'],
            'rejection_reason'       => ['nullable', 'string'],
            'can_reapply'            => ['nullable', 'boolean'],
            'reapply_recommendation' => ['nullable', 'string'],
        ]);

        $case = $this->service->findOrFail($id);
        $case = $this->service->completeCase($case, $data['result_type'], $data);

        return ApiResponse::success($case);
    }

    /**
     * POST /cases/{id}/submit-to-embassy — Отметить подачу в посольство.
     */
    public function submitToEmbassy(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'submitted_at'         => ['required', 'date'],
            'expected_result_date' => ['required', 'date'],
        ]);

        $case = $this->service->findOrFail($id);

        // Дата записи должна быть определена до подачи
        if (! $case->appointment_date) {
            return ApiResponse::validationError([
                'appointment_date' => ['Укажите дату записи в посольство перед подачей документов.'],
            ]);
        }

        $case->update([
            'submitted_at'         => $data['submitted_at'],
            'expected_result_date' => $data['expected_result_date'],
            'last_manager_update_at' => now(),
        ]);

        $this->service->moveToStage($case->fresh(), 'review', 'Документы поданы в посольство');

        return ApiResponse::success($case->fresh(['client', 'assignee', 'stageHistory']));
    }

    /**
     * PATCH /cases/{id}/expected-date — Обновить ожидаемую дату результата.
     */
    public function updateExpectedDate(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'expected_result_date' => ['required', 'date'],
            'notes'                => ['nullable', 'string'],
        ]);

        $case = $this->service->findOrFail($id);
        $case->update([
            'expected_result_date'   => $data['expected_result_date'],
            'last_manager_update_at' => now(),
        ]);

        return ApiResponse::success($case->fresh());
    }
}
