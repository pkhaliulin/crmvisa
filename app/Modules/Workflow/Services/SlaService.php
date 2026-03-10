<?php

namespace App\Modules\Workflow\Services;

use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Notification\Notifications\BusinessNotification;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Workflow\Models\SlaRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SlaService
{
    /**
     * Рабочие часы: 09:00 - 18:00 (UTC+5, Ташкент).
     * Выходные: суббота, воскресенье.
     */
    private const WORK_START_HOUR = 9;
    private const WORK_END_HOUR   = 18;
    private const WORK_HOURS_PER_DAY = 9; // 18 - 9
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
     * Учитывает рабочие часы (Пн-Пт, 09:00-18:00 UTC+5).
     */
    public function applyStageSla(CaseStage $caseStage, VisaCase $case): void
    {
        $stage = $caseStage->stage;

        // Приоритет 1: sla_hours из config/stages.php
        $configHours = config("stages.{$stage}.sla_hours");
        if ($configHours && $configHours > 0) {
            $caseStage->update([
                'sla_due_at' => $this->addBusinessHours(Carbon::now(), $configHours),
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
            // stage_sla_days — в рабочих днях, конвертируем в рабочие часы
            $hours = $stageSla[$stage] * self::WORK_HOURS_PER_DAY;
            $caseStage->update([
                'sla_due_at' => $this->addBusinessHours(Carbon::now(), $hours),
            ]);
        }
    }

    /**
     * Добавить N рабочих часов к дате, пропуская выходные (Сб/Вс)
     * и нерабочее время (до 09:00, после 18:00 UTC+5).
     */
    public function addBusinessHours(Carbon $from, int $hours): Carbon
    {
        $tz = 'Asia/Tashkent';
        $cursor = $from->copy()->setTimezone($tz);

        // Если сейчас выходной или нерабочее время — перенести на начало рабочего дня
        $cursor = $this->moveToWorkTime($cursor);

        $remainingHours = $hours;

        while ($remainingHours > 0) {
            // Сколько рабочих часов осталось до конца текущего рабочего дня
            $endOfDay = $cursor->copy()->setTime(self::WORK_END_HOUR, 0, 0);
            $hoursLeftToday = max(0, $cursor->floatDiffInHours($endOfDay, false));

            if ($hoursLeftToday >= $remainingHours) {
                $cursor->addHours($remainingHours);
                $remainingHours = 0;
            } else {
                $remainingHours -= (int) floor($hoursLeftToday);
                // Переход на следующий рабочий день
                $cursor->addDay()->setTime(self::WORK_START_HOUR, 0, 0);
                $cursor = $this->moveToWorkTime($cursor);
            }
        }

        return $cursor->setTimezone('UTC');
    }

    /**
     * Перенести дату на начало ближайшего рабочего времени,
     * если текущая дата — выходной или нерабочее время.
     */
    private function moveToWorkTime(Carbon $date): Carbon
    {
        // Пропустить выходные
        while ($date->isWeekend()) {
            $date->addDay()->setTime(self::WORK_START_HOUR, 0, 0);
        }

        // До начала рабочего дня — перенести на начало
        if ($date->hour < self::WORK_START_HOUR) {
            $date->setTime(self::WORK_START_HOUR, 0, 0);
        }

        // После конца рабочего дня — перенести на следующий рабочий день
        if ($date->hour >= self::WORK_END_HOUR) {
            $date->addDay()->setTime(self::WORK_START_HOUR, 0, 0);
            while ($date->isWeekend()) {
                $date->addDay();
            }
        }

        return $date;
    }

    /**
     * Пометить просроченные case_stages как overdue и отправить уведомления.
     * Вызывается из scheduler или при переходе этапа.
     */
    public function markOverdueStages(): int
    {
        $overdueStages = CaseStage::whereNull('exited_at')
            ->whereNotNull('sla_due_at')
            ->where('is_overdue', false)
            ->where('sla_due_at', '<', Carbon::now())
            ->with(['visaCase.client', 'visaCase.assignee', 'visaCase.agency'])
            ->get();

        if ($overdueStages->isEmpty()) {
            return 0;
        }

        $overdueStages->each->update(['is_overdue' => true]);

        // Эскалация: уведомление через NotificationService (#5)
        $notificationService = app(NotificationService::class);

        foreach ($overdueStages as $stage) {
            $case = $stage->visaCase;
            if (!$case || !$case->agency_id) {
                continue;
            }

            try {
                $notificationService->dispatch(
                    $case->agency_id,
                    'sla.violation',
                    new BusinessNotification('sla.violation', [
                        'case_id'      => $case->id,
                        'case_number'  => $case->case_number ?? $case->id,
                        'stage'        => $stage->stage,
                        'sla_due_at'   => $stage->sla_due_at->toDateTimeString(),
                        'client_name'  => $case->client?->name ?? 'N/A',
                        'message'      => "SLA нарушена: заявка #{$case->case_number} на этапе «{$stage->stage}» просрочена (дедлайн: {$stage->sla_due_at->format('d.m.Y H:i')})",
                    ]),
                    ['case' => $case, 'assigned_to' => $case->assigned_to],
                );
            } catch (\Throwable $e) {
                Log::warning('SLA violation notification failed', [
                    'case_id' => $case->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $overdueStages->count();
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
