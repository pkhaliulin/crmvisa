<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Services\ChecklistService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function __construct(private readonly ChecklistService $service) {}

    /**
     * GET /api/v1/cases/{caseId}/checklist
     * Чек-лист документов для заявки
     */
    public function index(Request $request, string $caseId): JsonResponse
    {
        $this->authorizeCase($request, $caseId);

        $data = $this->service->getForCase($caseId);

        return ApiResponse::success($data);
    }

    /**
     * POST /api/v1/cases/{caseId}/checklist/{itemId}/upload
     * Загрузить файл в слот чек-листа
     */
    public function upload(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        $request->validate([
            'file' => ['required', 'file', 'max:20480'],
        ]);

        $result = $this->service->uploadToSlot($item, $request->file('file'), $case);

        return ApiResponse::success($result, 'Document uploaded');
    }

    /**
     * PATCH /api/v1/cases/{caseId}/checklist/{itemId}/review
     * Одобрить / отклонить документ (менеджер)
     */
    public function review(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected,pending'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->service->reviewSlot($item, $validated['status'], $validated['notes'] ?? null);

        return ApiResponse::success($result, 'Status updated');
    }

    /**
     * POST /api/v1/cases/{caseId}/checklist
     * Добавить кастомный слот вручную (менеджер)
     */
    public function store(Request $request, string $caseId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_required' => ['boolean'],
        ]);

        $item = $this->service->addCustomSlot(
            $case,
            $validated['name'],
            $validated['description'] ?? null,
            $validated['is_required'] ?? false
        );

        return ApiResponse::created($item, 'Checklist item added');
    }

    /**
     * DELETE /api/v1/cases/{caseId}/checklist/{itemId}
     * Удалить кастомный слот
     */
    public function destroy(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        if ($item->requirement_id) {
            return ApiResponse::error('Cannot delete standard checklist items', null, 403);
        }

        $this->service->removeSlot($item);

        return ApiResponse::success(null, 'Checklist item deleted');
    }

    /**
     * PATCH /api/v1/cases/{caseId}/checklist/{itemId}/check
     * Отметить / снять отметку checkbox-слота
     */
    public function check(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        if ($item->type !== 'checkbox') {
            return ApiResponse::error('This item requires file upload, not checkbox');
        }

        $validated = $request->validate(['checked' => ['required', 'boolean']]);
        $result = $this->service->toggleCheck($item, $validated['checked']);

        return ApiResponse::success($result, 'Updated');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authorizeCase(Request $request, string $caseId): VisaCase
    {
        return VisaCase::where('id', $caseId)
                       ->where('agency_id', $request->user()->agency_id)
                       ->firstOrFail();
    }

    private function authorizeItem(Request $request, string $caseId, string $itemId): CaseChecklist
    {
        return CaseChecklist::where('id', $itemId)
                             ->where('case_id', $caseId)
                             ->where('agency_id', $request->user()->agency_id)
                             ->firstOrFail();
    }
}
