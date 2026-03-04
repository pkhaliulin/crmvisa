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
     * Рассчитать critical_date на основе даты вылета и данных посольства.
     * critical_date = travel_date − (processing_days_standard + appointment_wait_days + buffer_days_recommended)
     */
    public function calculateCriticalDateFromTravel(string $countryCode, Carbon $travelDate): ?Carbon
    {
        $country = \Illuminate\Support\Facades\DB::table('portal_countries')
            ->where('country_code', strtoupper($countryCode))
            ->first(['processing_days_standard', 'appointment_wait_days', 'buffer_days_recommended']);

        if (! $country) {
            return null;
        }

        $totalDays = (int) ($country->processing_days_standard ?? 0)
                   + (int) ($country->appointment_wait_days    ?? 0)
                   + (int) ($country->buffer_days_recommended  ?? 14);

        if ($totalDays <= 0) {
            return null;
        }

        return $travelDate->copy()->subDays($totalDays);
    }

    /**
     * Улучшенный расчёт critical_date с учётом per-visa-type данных.
     * Сначала ищет в country_visa_type_settings, затем fallback на portal_countries.
     */
    public function calculateCriticalDateFromTravelEnhanced(string $countryCode, string $visaType, Carbon $travelDate): ?Carbon
    {
        $setting = \App\Modules\Owner\Models\CountryVisaTypeSetting::findSetting($countryCode, $visaType);

        if ($setting) {
            return $travelDate->copy()->subDays($setting->recommended_days_before_departure);
        }

        // Fallback: старая логика из portal_countries
        return $this->calculateCriticalDateFromTravel($countryCode, $travelDate);
    }

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
        $stage = $caseStage->stage;

        // Приоритет 1: sla_hours из config/stages.php
        $configHours = config("stages.{$stage}.sla_hours");
        if ($configHours && $configHours > 0) {
            $caseStage->update([
                'sla_due_at' => Carbon::now()->addHours($configHours),
            ]);
            return;
        }

        // Приоритет 2: fallback на SlaRule из БД (stage_sla_days)
        $rule = SlaRule::findRule($case->country_code, $case->visa_type);

        if (! $rule || empty($rule->stage_sla_days)) {
            return;
        }

        $stageSla = $rule->stage_sla_days;

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
