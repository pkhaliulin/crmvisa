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
        if ($request->filled('stage')) {
            return ApiResponse::success(
                $this->service->byStage($request->stage)
            );
        }

        return ApiResponse::paginated($this->service->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'    => ['required', 'uuid', 'exists:clients,id'],
            'country_code' => ['required', 'string', 'size:2'],
            'visa_type'    => ['required', 'string', 'max:50'],
            'assigned_to'  => ['nullable', 'uuid', 'exists:users,id'],
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
        $data = $request->validate([
            'assigned_to'  => ['sometimes', 'nullable', 'uuid', 'exists:users,id'],
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
