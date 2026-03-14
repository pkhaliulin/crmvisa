<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Requests\CompleteCaseRequest;
use App\Modules\Case\Requests\MoveCaseStageRequest;
use App\Modules\Case\Requests\StoreCaseRequest;
use App\Modules\Case\Requests\UpdateCaseRequest;
use App\Modules\Case\Resources\CaseResource;
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
            'status'       => ['nullable', 'string', 'in:overdue,critical,active'],
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

        // Фильтр по статусу: overdue / critical / active
        if (!empty($filters['status'])) {
            $today = now()->toDateString();
            if ($filters['status'] === 'overdue') {
                $query->whereNotIn('cases.stage', ['result'])
                    ->whereNotNull('critical_date')
                    ->whereDate('critical_date', '<', $today);
            } elseif ($filters['status'] === 'critical') {
                $query->whereNotIn('cases.stage', ['result'])
                    ->whereNotNull('critical_date')
                    ->whereDate('critical_date', '>=', $today)
                    ->whereDate('critical_date', '<=', now()->addDays(5)->toDateString());
            } elseif ($filters['status'] === 'active') {
                $query->whereNotIn('cases.stage', ['result']);
            }
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

        return ApiResponse::paginated($query->paginate(20), 'Success', CaseResource::class);
    }

    public function store(StoreCaseRequest $request): JsonResponse
    {
        $data = $request->validated();

        $case = $this->service->createCase($data);

        return ApiResponse::created(new CaseResource($case));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $case = $this->service->findOrFail($id);
        $this->authorize('view', $case);

        $case->load(['client', 'assignee', 'stageHistory']);

        $caseResource = new CaseResource($case);

        // Client portrait data (единый источник — public_users)
        $portrait = null;
        if ($case->client) {
            $client = $case->client;
            $publicUser = $client->public_user_id
                ? \App\Modules\PublicPortal\Models\PublicUser::find($client->public_user_id)
                : null;
            $visaborScores = $publicUser
                ? \App\Modules\PublicPortal\Models\PublicScoreCache::where('public_user_id', $publicUser->id)
                    ->orderByDesc('score')
                    ->get(['country_code', 'score', 'breakdown', 'calculated_at'])
                : [];
            $caseCounts = \App\Modules\Case\Models\VisaCase::where('client_id', $client->id)
                ->selectRaw("
                    COUNT(*) FILTER (WHERE public_status NOT IN ('draft','cancelled')) as total,
                    COUNT(*) FILTER (WHERE stage = 'result' AND result_type = 'approved') as approved,
                    COUNT(*) FILTER (WHERE stage = 'result' AND result_type = 'rejected') as rejected
                ")
                ->first();

            $portrait = [
                'date_of_birth'       => $client->date_of_birth,
                'passport_number'     => $client->passport_number,
                'passport_expires_at' => $client->passport_expires_at,
                'total_cases'         => $caseCounts->total ?? 0,
                'approved_cases'      => $caseCounts->approved ?? 0,
                'rejected_cases'      => $caseCounts->rejected ?? 0,
                'profile'             => $publicUser ? [
                    'marital_status'     => $publicUser->marital_status,
                    'children_count'     => $publicUser->children_count,
                    'employment_type'    => $publicUser->employment_type,
                    'employed_years'     => $publicUser->employed_years,
                    'monthly_income_usd' => $publicUser->monthly_income_usd,
                    'has_property'       => $publicUser->has_property,
                    'has_car'            => $publicUser->has_car,
                    'has_schengen_visa'  => $publicUser->has_schengen_visa,
                    'has_us_visa'        => $publicUser->has_us_visa,
                    'had_visa_refusal'   => $publicUser->had_visa_refusal,
                    'refusals_count'     => $publicUser->refusals_count,
                    'had_overstay'       => $publicUser->had_overstay,
                    'had_deportation'    => $publicUser->had_deportation,
                    'education_level'    => $publicUser->education_level,
                ] : null,
                'scores'              => $visaborScores,
            ];
        }

        $paymentSummary = app(\App\Modules\Case\Services\CasePaymentService::class)
            ->getPaymentSummary($case);

        return ApiResponse::success([
            'case' => $caseResource,
            'allowed_transitions' => CaseService::ALLOWED_TRANSITIONS,
            'client_portrait' => $portrait,
            'payment_summary' => $paymentSummary,
        ]);
    }

    public function update(UpdateCaseRequest $request, string $id): JsonResponse
    {
        $case = $this->service->findOrFail($id);
        $this->authorize('update', $case);

        $case = $this->service->updateCase($id, $request->validated());

        return ApiResponse::success(new CaseResource($case));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $case = $this->service->findOrFail($id);
        $this->authorize('delete', $case);

        $this->service->delete($id);

        return ApiResponse::success(null, 'Case deleted.');
    }

    public function moveStage(MoveCaseStageRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        /** @var VisaCase */
        $case = $this->service->findOrFail($id);
        $this->authorize('moveStage', $case);

        $case = $this->service->moveToStage($case, $data['stage'], $data['notes'] ?? null);

        return ApiResponse::success(new CaseResource($case));
    }

    public function critical(Request $request): JsonResponse
    {
        return ApiResponse::success($this->service->critical($request->user()->agency_id));
    }

    /**
     * POST /cases/{id}/complete — Завершить заявку (одобрено/отказ).
     */
    public function complete(CompleteCaseRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $case = $this->service->findOrFail($id);
        $this->authorize('complete', $case);

        if ($case->stage !== 'review') {
            return ApiResponse::error('Завершить заявку можно только на этапе «Рассмотрение».', null, 422);
        }

        $case = $this->service->completeCase($case, $data['result_type'], $data);

        return ApiResponse::success(new CaseResource($case));
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
        $this->authorize('moveStage', $case);

        if ($case->stage !== 'ready') {
            return ApiResponse::error('Подать в посольство можно только на этапе «Готов к подаче».', null, 422);
        }

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

        return ApiResponse::success(new CaseResource($case->fresh(['client', 'assignee', 'stageHistory'])));
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
        $this->authorize('update', $case);
        $case->update([
            'expected_result_date'   => $data['expected_result_date'],
            'last_manager_update_at' => now(),
        ]);

        return ApiResponse::success(new CaseResource($case->fresh()));
    }

    /**
     * POST /cases/{id}/cancel — Отменить заявку (owner/superadmin).
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $case = $this->service->findOrFail($id);
        $this->authorize('cancel', $case);

        $case = $this->service->cancelCase($case, $data['reason'] ?? null);

        return ApiResponse::success(new CaseResource($case));
    }
}
