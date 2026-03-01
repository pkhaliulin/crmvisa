<?php

namespace App\Modules\Workflow\Services;

use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Workflow\Models\SlaRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SlaService
{
    /**
     * Рассчитать critical_date для заявки по правилу SLA.
     * Возвращает null если правило не найдено.
     */
    public function calculateCriticalDate(string $countryCode, string $visaType, ?Carbon $from = null): ?Carbon
    {
        $rule = SlaRule::findRule($countryCode, $visaType);

        if (! $rule) {
            return null;
        }

        return ($from ?? Carbon::now())->addDays($rule->max_days);
    }

    /**
     * Установить sla_due_at для CaseStage на основе stage_sla_days из правила SLA.
     */
    public function applyStageSla(CaseStage $caseStage, VisaCase $case): void
    {
        $rule = SlaRule::findRule($case->country_code, $case->visa_type);

        if (! $rule || empty($rule->stage_sla_days)) {
            return;
        }

        $stageSla = $rule->stage_sla_days;
        $stage    = $caseStage->stage;

        if (isset($stageSla[$stage]) && $stageSla[$stage] > 0) {
            $caseStage->update([
                'sla_due_at' => Carbon::now()->addDays($stageSla[$stage]),
            ]);
        }
    }

    /**
     * Пометить просроченные case_stages как overdue.
     * Вызывается из scheduler или при переходе этапа.
     */
    public function markOverdueStages(): int
    {
        return CaseStage::whereNull('exited_at')
            ->whereNotNull('sla_due_at')
            ->where('is_overdue', false)
            ->where('sla_due_at', '<', Carbon::now())
            ->update(['is_overdue' => true]);
    }

    /**
     * Найти все заявки, по которым SLA истекает в течение warning_days.
     * Загружает правила одним запросом, избегая N+1.
     */
    public function findCasesApproachingDeadline(): Collection
    {
        // Загружаем все правила одним запросом
        $rules = SlaRule::all()->keyBy(fn ($r) => "{$r->country_code}:{$r->visa_type}");

        return VisaCase::query()
            ->whereNotNull('critical_date')
            ->whereNotIn('stage', ['result'])
            ->whereHas('agency', fn ($q) => $q->where('is_active', true))
            ->with(['client', 'assignee', 'agency'])
            ->get()
            ->filter(function (VisaCase $case) use ($rules) {
                $rule        = $rules->get("{$case->country_code}:{$case->visa_type}");
                $warningDays = $rule ? $rule->warning_days : 5;
                $daysLeft    = Carbon::now()->diffInDays($case->critical_date, false);

                return $daysLeft >= 0 && $daysLeft <= $warningDays;
            });
    }
}
