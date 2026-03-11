<?php

namespace App\Modules\Task\Controllers;

use App\Modules\Task\Models\AgencyTask;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * Список задач с фильтрацией.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AgencyTask::with([
            'creator:id,name',
            'assignee:id,name',
            'visaCase:id,case_number,country_code',
        ]);

        $userId = $request->user()->id;

        // Фильтры
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }
        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }
        if ($request->filled('assigned_to')) {
            $val = $request->assigned_to === 'me' ? $userId : $request->assigned_to;
            $query->byAssignee($val);
        }
        if ($request->filled('created_by')) {
            $val = $request->created_by === 'me' ? $userId : $request->created_by;
            $query->byCreator($val);
        }
        if ($request->filled('case_id')) {
            $query->where('case_id', $request->case_id);
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }
        if ($request->boolean('due_today')) {
            $query->dueToday();
        }
        if ($request->boolean('awaiting_verification')) {
            $query->awaitingVerification();
        }
        if ($request->boolean('recurring')) {
            $query->recurring();
        }

        // Поиск
        if ($request->filled('search')) {
            $query->where('title', 'ilike', '%' . $request->search . '%');
        }

        // Сортировка: терминальные вниз, просроченные наверху, приоритет, дата
        $query->orderByRaw("
            CASE WHEN status IN ('closed','cancelled') THEN 1 ELSE 0 END,
            CASE WHEN due_date IS NOT NULL AND due_date < CURRENT_DATE AND status NOT IN ('closed','cancelled','completed','verified') THEN 0 ELSE 1 END,
            CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END,
            COALESCE(due_date, '2099-12-31')
        ");

        $tasks = $query->paginate($request->integer('per_page', 50));

        return ApiResponse::success($tasks);
    }

    /**
     * Создать задачу.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string|max:2000',
            'priority'        => ['nullable', Rule::in(AgencyTask::PRIORITIES)],
            'assigned_to'     => 'nullable|uuid|exists:users,id',
            'case_id'         => 'nullable|uuid|exists:cases,id',
            'due_date'        => 'nullable|date|after_or_equal:today',
            'recurrence_rule' => ['nullable', Rule::in(AgencyTask::RECURRENCE_RULES)],
        ]);

        $task = AgencyTask::create([
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'priority'        => $data['priority'] ?? 'medium',
            'assigned_to'     => $data['assigned_to'] ?? null,
            'case_id'         => $data['case_id'] ?? null,
            'due_date'        => $data['due_date'] ?? null,
            'recurrence_rule' => $data['recurrence_rule'] ?? null,
            'created_by'      => $request->user()->id,
            'status'          => 'new',
        ]);

        $task->load(['creator:id,name', 'assignee:id,name', 'visaCase:id,case_number,country_code']);

        return ApiResponse::created($task);
    }

    /**
     * Показать задачу.
     */
    public function show(string $id): JsonResponse
    {
        $task = AgencyTask::with([
            'creator:id,name',
            'assignee:id,name',
            'completedByUser:id,name',
            'verifiedByUser:id,name',
            'visaCase:id,case_number,country_code,stage',
        ])->findOrFail($id);

        return ApiResponse::success($task);
    }

    /**
     * Обновить задачу.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $task = AgencyTask::findOrFail($id);

        $data = $request->validate([
            'title'           => 'sometimes|string|max:255',
            'description'     => 'nullable|string|max:2000',
            'priority'        => ['sometimes', Rule::in(AgencyTask::PRIORITIES)],
            'status'          => ['sometimes', Rule::in(AgencyTask::STATUSES)],
            'assigned_to'     => 'nullable|uuid|exists:users,id',
            'case_id'         => 'nullable|uuid|exists:cases,id',
            'due_date'        => 'nullable|date',
            'recurrence_rule' => ['nullable', Rule::in(AgencyTask::RECURRENCE_RULES)],
        ]);

        $this->applyStatusTransition($task, $data, $request->user()->id);

        $task->update($data);
        $task->load(['creator:id,name', 'assignee:id,name', 'visaCase:id,case_number,country_code']);

        return ApiResponse::success($task);
    }

    /**
     * Удалить задачу.
     */
    public function destroy(string $id): JsonResponse
    {
        $task = AgencyTask::findOrFail($id);
        $task->delete();

        return ApiResponse::success(null, message: 'Задача удалена');
    }

    /**
     * Быстрая смена статуса (следующий шаг в цикле).
     * new → accepted → completed → verified → closed
     */
    public function transition(Request $request, string $id): JsonResponse
    {
        $task = AgencyTask::findOrFail($id);
        $userId = $request->user()->id;
        $role = $request->user()->role;

        $nextStatus = $this->resolveNextStatus($task->status, $role);
        if (! $nextStatus) {
            return ApiResponse::error('Нет доступного перехода для текущего статуса', 422);
        }

        $updates = ['status' => $nextStatus];

        if ($nextStatus === 'completed') {
            $updates['completed_at'] = now();
            $updates['completed_by'] = $userId;
        }
        if ($nextStatus === 'verified') {
            $updates['verified_at'] = now();
            $updates['verified_by'] = $userId;
        }

        $task->update($updates);

        // Повторяющаяся задача: создать следующую при закрытии
        if (in_array($nextStatus, ['closed', 'verified']) && $task->isRecurring()) {
            $task->createNextRecurrence();
        }

        $task->load(['creator:id,name', 'assignee:id,name']);

        return ApiResponse::success($task);
    }

    /**
     * Отложить / отменить задачу.
     */
    public function setStatus(Request $request, string $id): JsonResponse
    {
        $task = AgencyTask::findOrFail($id);
        $data = $request->validate([
            'status' => ['required', Rule::in(['deferred', 'cancelled', 'new'])],
        ]);

        $updates = ['status' => $data['status']];

        // При возобновлении сбрасываем completed/verified
        if ($data['status'] === 'new') {
            $updates['completed_at'] = null;
            $updates['completed_by'] = null;
            $updates['verified_at'] = null;
            $updates['verified_by'] = null;
        }

        $task->update($updates);
        $task->load(['creator:id,name', 'assignee:id,name']);

        return ApiResponse::success($task);
    }

    /**
     * Счётчики для дашборда.
     */
    public function counters(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $counters = [
            'total_active'          => AgencyTask::active()->count(),
            'my_tasks'              => AgencyTask::active()->byAssignee($userId)->count(),
            'overdue'               => AgencyTask::overdue()->count(),
            'my_overdue'            => AgencyTask::overdue()->byAssignee($userId)->count(),
            'due_today'             => AgencyTask::dueToday()->count(),
            'awaiting_verification' => AgencyTask::awaitingVerification()->count(),
            'created_by_me'         => AgencyTask::active()->byCreator($userId)->count(),
        ];

        return ApiResponse::success($counters);
    }

    // -- Private helpers ────────────────────────────────────

    private function resolveNextStatus(string $current, string $role): ?string
    {
        return match ($current) {
            'new'       => 'accepted',
            'accepted'  => 'completed',
            'completed' => in_array($role, ['owner', 'superadmin']) ? 'verified' : null,
            'verified'  => 'closed',
            'deferred'  => 'accepted',
            default     => null,
        };
    }

    private function applyStatusTransition(AgencyTask $task, array &$data, string $userId): void
    {
        if (! isset($data['status'])) {
            return;
        }

        $newStatus = $data['status'];
        $oldStatus = $task->status;

        // completed: ставим completed_at/completed_by
        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $data['completed_at'] = now();
            $data['completed_by'] = $userId;
        }

        // verified: ставим verified_at/verified_by
        if ($newStatus === 'verified' && $oldStatus !== 'verified') {
            $data['verified_at'] = now();
            $data['verified_by'] = $userId;
        }

        // Реоткрытие: сбрасываем
        if (in_array($newStatus, ['new', 'accepted']) && in_array($oldStatus, ['completed', 'verified', 'closed'])) {
            $data['completed_at'] = null;
            $data['completed_by'] = null;
            $data['verified_at'] = null;
            $data['verified_by'] = null;
        }

        // Повторяющаяся задача: создать следующую при закрытии
        if (in_array($newStatus, ['closed', 'verified']) && $task->isRecurring()) {
            $task->createNextRecurrence();
        }
    }
}
