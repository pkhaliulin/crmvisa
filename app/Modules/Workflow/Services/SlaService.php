<?php

namespace App\Modules\Workflow\Services;

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
     * Найти все заявки, по которым SLA истекает через ≤ warning_days дней.
     * Группирует по agency_id для батчевых уведомлений.
     */
    public function findCasesApproachingDeadline(): Collection
    {
        return VisaCase::query()
            ->whereNotNull('critical_date')
            ->whereNotIn('stage', ['result'])
            ->whereHas('agency', fn ($q) => $q->where('is_active', true))
            ->with(['client', 'assignee', 'agency'])
            ->get()
            ->filter(function (VisaCase $case) {
                $rule = SlaRule::findRule($case->country_code, $case->visa_type);
                $warningDays = $rule ? $rule->warning_days : 5;

                $daysLeft = Carbon::now()->diffInDays($case->critical_date, false);

                return $daysLeft >= 0 && $daysLeft <= $warningDays;
            });
    }
}
