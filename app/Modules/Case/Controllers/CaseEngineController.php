<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Models\VisaCaseRule;
use App\Modules\Case\Services\FranceFormService;
use App\Modules\Case\Services\VisaCaseEngineService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseEngineController extends Controller
{
    /**
     * GET /cases/{id}/engine/readiness
     */
    public function readiness(Request $request, string $id): JsonResponse
    {
        $case = VisaCase::where('agency_id', $request->user()->agency_id)
            ->findOrFail($id);

        if (! $case->visa_case_rule_id) {
            // Попытка автоинициализации
            $initialized = VisaCaseEngineService::initializeEngine($case);
            if (! $initialized) {
                return ApiResponse::error('Для данной страны/типа визы нет правил engine.', null, 404);
            }
            $case->refresh();
        }

        return ApiResponse::success([
            'readiness_score' => $case->readiness_score,
            'missing_items'   => $case->missing_items ?? [],
            'next_action'     => $case->next_action,
            'rule'            => [
                'id'                   => $case->visa_case_rule_id,
                'embassy_platform'     => $case->embassy_platform,
                'submission_method'    => $case->submission_method,
                'appointment_required' => $case->appointment_required,
                'biometrics_required'  => $case->biometrics_required,
                'reference_number'     => $case->reference_number,
            ],
        ]);
    }

    /**
     * GET /cases/{id}/engine/checkpoints
     */
    public function checkpoints(Request $request, string $id): JsonResponse
    {
        $case = VisaCase::where('agency_id', $request->user()->agency_id)
            ->findOrFail($id);

        $statuses = $case->checkpointStatuses()
            ->with('checkpoint')
            ->get()
            ->map(fn ($s) => [
                'id'           => $s->id,
                'checkpoint_id'=> $s->checkpoint_id,
                'slug'         => $s->checkpoint->slug ?? null,
                'title'        => $s->checkpoint->title ?? null,
                'description'  => $s->checkpoint->description ?? null,
                'stage'        => $s->checkpoint->stage ?? null,
                'check_type'   => $s->checkpoint->check_type ?? null,
                'is_blocking'  => $s->checkpoint->is_blocking ?? false,
                'is_completed' => $s->is_completed,
                'completed_at' => $s->completed_at?->toIso8601String(),
                'notes'        => $s->notes,
            ])
            ->groupBy('stage');

        return ApiResponse::success($statuses);
    }

    /**
     * PATCH /cases/{id}/engine/checkpoints/{cpId}
     */
    public function toggleCheckpoint(Request $request, string $id, string $cpId): JsonResponse
    {
        $request->validate([
            'is_completed' => ['required', 'boolean'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $case = VisaCase::where('agency_id', $request->user()->agency_id)
            ->findOrFail($id);

        $status = VisaCaseEngineService::toggleCheckpoint(
            $case,
            $cpId,
            $request->boolean('is_completed'),
            $request->user()->id,
            $request->input('notes')
        );

        return ApiResponse::success([
            'is_completed'    => $status->is_completed,
            'completed_at'    => $status->completed_at?->toIso8601String(),
            'readiness_score' => $case->fresh()->readiness_score,
        ]);
    }

    /**
     * GET /cases/{id}/engine/form
     */
    public function form(Request $request, string $id): JsonResponse
    {
        $case = VisaCase::where('agency_id', $request->user()->agency_id)
            ->findOrFail($id);

        $steps = FranceFormService::getFormFields($case);

        return ApiResponse::success($steps);
    }

    /**
     * PUT /cases/{id}/engine/form/{step}
     */
    public function saveFormStep(Request $request, string $id, int $step): JsonResponse
    {
        $request->validate([
            'data' => ['required', 'array'],
        ]);

        $case = VisaCase::where('agency_id', $request->user()->agency_id)
            ->findOrFail($id);

        FranceFormService::saveFormStep($case, $step, $request->input('data'));

        return ApiResponse::success([
            'readiness_score' => $case->fresh()->readiness_score,
        ], 'Данные шага сохранены.');
    }

    /**
     * POST /cases/{id}/engine/form/prefill
     */
    public function prefillForm(Request $request, string $id): JsonResponse
    {
        $case = VisaCase::where('agency_id', $request->user()->agency_id)
            ->findOrFail($id);

        $prefilled = FranceFormService::applyPrefill($case);

        return ApiResponse::success([
            'prefilled_count' => count($prefilled),
            'prefilled_keys'  => array_keys($prefilled),
            'readiness_score' => $case->fresh()->readiness_score,
        ], 'Поля автозаполнены из данных клиента.');
    }

    /**
     * GET /cases/{id}/engine/form/progress
     */
    public function formProgress(Request $request, string $id): JsonResponse
    {
        $case = VisaCase::where('agency_id', $request->user()->agency_id)
            ->findOrFail($id);

        return ApiResponse::success(FranceFormService::getFormProgress($case));
    }

    /**
     * POST /cases/{id}/engine/init
     */
    public function initialize(Request $request, string $id): JsonResponse
    {
        $case = VisaCase::where('agency_id', $request->user()->agency_id)
            ->findOrFail($id);

        if ($case->visa_case_rule_id) {
            return ApiResponse::error('Engine уже инициализирован для этой заявки.', null, 409);
        }

        $ok = VisaCaseEngineService::initializeEngine($case);
        if (! $ok) {
            return ApiResponse::error('Нет правил engine для этой страны/типа визы.', null, 404);
        }

        $case->refresh();

        return ApiResponse::created([
            'visa_case_rule_id' => $case->visa_case_rule_id,
            'readiness_score'   => $case->readiness_score,
            'missing_items'     => $case->missing_items,
            'next_action'       => $case->next_action,
        ], 'Visa Case Engine инициализирован.');
    }

    /**
     * GET /engine/rules
     */
    public function rules(): JsonResponse
    {
        $rules = VisaCaseRule::active()
            ->select('id', 'country_code', 'visa_type', 'visa_subtype', 'applicant_type',
                'embassy_platform', 'submission_method', 'appointment_required', 'biometrics_required',
                'processing_days_min', 'processing_days_max', 'consular_fee_eur', 'max_stay_days')
            ->orderBy('country_code')
            ->orderBy('visa_type')
            ->get();

        return ApiResponse::success($rules);
    }

    /**
     * GET /engine/rules/{countryCode}/{visaType}
     */
    public function ruleDetail(string $countryCode, string $visaType): JsonResponse
    {
        $rule = VisaCaseRule::resolveRule(strtoupper($countryCode), $visaType);

        if (! $rule) {
            return ApiResponse::error('Правило не найдено.', null, 404);
        }

        $rule->load(['requiredDocuments', 'checkpoints', 'fieldMappings']);

        return ApiResponse::success([
            'rule'       => $rule,
            'documents'  => $rule->requiredDocuments,
            'checkpoints'=> $rule->checkpoints,
            'form_steps' => $rule->fieldMappings->groupBy('step_number')->map(fn ($fields, $step) => [
                'step'   => $step,
                'title'  => $fields->first()->step_title,
                'fields' => $fields->count(),
            ])->values(),
        ]);
    }
}
