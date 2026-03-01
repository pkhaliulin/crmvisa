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

        $query = VisaCase::where('cases.agency_id', $agencyId)
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
        if ($request->filled('stage')) {
            $query->where('stage', $request->stage);
        }

        // Фильтр по менеджеру
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Фильтр по приоритету
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Фильтр по стране
        if ($request->filled('country_code')) {
            $query->where('country_code', strtoupper($request->country_code));
        }

        // Поиск по имени клиента
        if ($request->filled('q')) {
            $query->join('clients', 'clients.id', '=', 'cases.client_id')
                  ->where('clients.name', 'ilike', '%' . $request->q . '%');
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
            'visa_type'    => ['required', 'string', 'max:50'],
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
            'notes'        => ['sometimes', 'nullable', 'string'],
        ]);

        $case = $this->service->update($id, $data);

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

    public function critical(): JsonResponse
    {
        return ApiResponse::success($this->service->critical());
    }
}
