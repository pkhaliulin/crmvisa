<?php

namespace App\Modules\Case\Services;

use App\Modules\Case\Models\CaseCheckpointStatus;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Models\VisaCaseCheckpoint;
use App\Modules\Case\Models\VisaCaseRule;
use App\Modules\Document\Models\CaseChecklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VisaCaseEngineService
{
    /**
     * Найти подходящее правило для заявки.
     */
    public static function resolveRule(VisaCase $case): ?VisaCaseRule
    {
        return VisaCaseRule::resolveRule(
            $case->country_code,
            $case->visa_type,
            $case->visa_subtype,
            $case->applicant_type ?? 'adult'
        );
    }

    /**
     * Инициализировать engine для заявки: привязать rule, создать чекпоинты и расширенный чеклист.
     */
    public static function initializeEngine(VisaCase $case): bool
    {
        $rule = static::resolveRule($case);
        if (! $rule) {
            return false;
        }

        DB::transaction(function () use ($case, $rule) {
            // Привязать rule к заявке
            $case->update([
                'visa_case_rule_id'   => $rule->id,
                'embassy_platform'    => $rule->embassy_platform,
                'submission_method'   => $rule->submission_method,
                'appointment_required'=> $rule->appointment_required,
                'biometrics_required' => $rule->biometrics_required,
            ]);

            // Создать инстансы чекпоинтов
            static::createCheckpointStatuses($case, $rule);

            // Создать расширенный чеклист документов из engine
            static::createEngineChecklist($case, $rule);

            // Рассчитать readiness
            static::refreshCaseReadiness($case);
        });

        return true;
    }

    /**
     * Создать инстансы чекпоинтов для заявки.
     */
    private static function createCheckpointStatuses(VisaCase $case, VisaCaseRule $rule): void
    {
        $checkpoints = $rule->checkpoints()->where('is_active', true)->get();

        $rows = [];
        foreach ($checkpoints as $cp) {
            $rows[] = [
                'id'            => Str::uuid()->toString(),
                'case_id'       => $case->id,
                'checkpoint_id' => $cp->id,
                'is_completed'  => false,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        if ($rows) {
            DB::table('case_checkpoint_statuses')->insert($rows);
        }
    }

    /**
     * Создать чеклист документов из engine required documents.
     */
    private static function createEngineChecklist(VisaCase $case, VisaCaseRule $rule): void
    {
        $docs = $rule->requiredDocuments()->where('is_active', true)->get();

        $rows = [];
        foreach ($docs as $doc) {
            // Проверяем applicant_types если указаны
            if ($doc->applicant_types && ! in_array($case->applicant_type ?? 'adult', $doc->applicant_types)) {
                continue;
            }

            $rows[] = [
                'id'              => Str::uuid()->toString(),
                'agency_id'       => $case->agency_id,
                'case_id'         => $case->id,
                'type'            => 'upload',
                'name'            => $doc->name,
                'description'     => $doc->description,
                'is_required'     => $doc->requirement_level === 'required',
                'status'          => 'pending',
                'sort_order'      => $doc->display_order,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        if ($rows) {
            DB::table('case_checklist')->insert($rows);
        }
    }

    /**
     * Рассчитать readiness score (0-100) и обновить missing_items + next_action.
     */
    public static function refreshCaseReadiness(VisaCase $case): void
    {
        if (! $case->visa_case_rule_id) {
            return;
        }

        $missing = [];
        $weights = ['checkpoints' => 40, 'documents' => 40, 'form' => 20];
        $scores  = ['checkpoints' => 0, 'documents' => 0, 'form' => 0];

        // 1. Чекпоинты
        $checkpointStatuses = CaseCheckpointStatus::where('case_id', $case->id)
            ->with('checkpoint')
            ->get();

        $totalCp = $checkpointStatuses->count();
        $doneCp  = $checkpointStatuses->where('is_completed', true)->count();

        if ($totalCp > 0) {
            $scores['checkpoints'] = (int) round(($doneCp / $totalCp) * $weights['checkpoints']);
        } else {
            $scores['checkpoints'] = $weights['checkpoints']; // no checkpoints = full score
        }

        foreach ($checkpointStatuses->where('is_completed', false) as $cs) {
            $missing[] = [
                'type' => 'checkpoint',
                'name' => $cs->checkpoint->title ?? $cs->checkpoint_id,
                'blocking' => $cs->checkpoint->is_blocking ?? false,
                'stage' => $cs->checkpoint->stage ?? null,
            ];
        }

        // 2. Документы (из case_checklist)
        $checklist = CaseChecklist::where('case_id', $case->id)
            ->where('is_required', true)
            ->get();

        $totalDocs = $checklist->count();
        $doneDocs  = $checklist->whereIn('status', ['approved', 'uploaded'])->count();

        if ($totalDocs > 0) {
            $scores['documents'] = (int) round(($doneDocs / $totalDocs) * $weights['documents']);
        } else {
            $scores['documents'] = $weights['documents'];
        }

        foreach ($checklist->whereNotIn('status', ['approved', 'uploaded']) as $item) {
            $missing[] = [
                'type' => 'document',
                'name' => $item->name,
                'status' => $item->status,
            ];
        }

        // 3. Форма анкеты
        $rule = $case->rule;
        if ($rule) {
            $totalFields = $rule->fieldMappings()->where('is_required', true)->where('is_active', true)->count();
            $formData = $case->form_data ?? [];
            $filledFields = 0;

            if ($totalFields > 0) {
                $requiredKeys = $rule->fieldMappings()
                    ->where('is_required', true)
                    ->where('is_active', true)
                    ->pluck('field_key')
                    ->toArray();

                foreach ($requiredKeys as $key) {
                    if (! empty($formData[$key])) {
                        $filledFields++;
                    } else {
                        $mapping = $rule->fieldMappings()->where('field_key', $key)->first();
                        $missing[] = [
                            'type' => 'field',
                            'name' => $mapping->field_label ?? $key,
                            'step' => $mapping->step_number ?? null,
                        ];
                    }
                }

                $scores['form'] = (int) round(($filledFields / $totalFields) * $weights['form']);
            } else {
                $scores['form'] = $weights['form'];
            }
        }

        $readinessScore = array_sum($scores);
        $nextAction = static::determineNextAction($missing, $case);

        $case->update([
            'readiness_score' => $readinessScore,
            'missing_items'   => $missing,
            'next_action'     => $nextAction,
        ]);
    }

    /**
     * Определить следующее приоритетное действие.
     */
    private static function determineNextAction(array $missing, VisaCase $case): ?string
    {
        if (empty($missing)) {
            return $case->reference_number ? null : 'Получить reference number на портале посольства';
        }

        // Приоритет: blocking checkpoints > documents > fields
        $blocking = collect($missing)->where('type', 'checkpoint')->where('blocking', true)->first();
        if ($blocking) {
            return $blocking['name'];
        }

        $doc = collect($missing)->where('type', 'document')->first();
        if ($doc) {
            return "Загрузите: {$doc['name']}";
        }

        $field = collect($missing)->where('type', 'field')->first();
        if ($field) {
            return "Заполните: {$field['name']}";
        }

        return null;
    }

    /**
     * Отметить чекпоинт выполненным/невыполненным.
     */
    public static function toggleCheckpoint(VisaCase $case, string $checkpointId, bool $completed, ?string $userId = null, ?string $notes = null): CaseCheckpointStatus
    {
        $status = CaseCheckpointStatus::where('case_id', $case->id)
            ->where('checkpoint_id', $checkpointId)
            ->firstOrFail();

        $status->update([
            'is_completed' => $completed,
            'completed_at' => $completed ? now() : null,
            'completed_by' => $completed ? $userId : null,
            'notes'        => $notes,
        ]);

        static::refreshCaseReadiness($case);

        return $status->fresh();
    }
}
