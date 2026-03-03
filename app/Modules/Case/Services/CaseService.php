<?php

namespace App\Modules\Case\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Events\CaseCreated;
use App\Modules\Case\Events\CaseStatusChanged;
use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Repositories\CaseRepository;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\Notification\Notifications\CaseStageChangedNotification;
use App\Modules\Workflow\Services\SlaService;
use App\Support\Abstracts\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaseService extends BaseService
{
    public function __construct(
        CaseRepository $repository,
        private SlaService $slaService,
        private ChecklistService $checklistService,
    ) {
        parent::__construct($repository);
    }

    private function caseRepository(): CaseRepository
    {
        /** @var CaseRepository */
        return $this->repository;
    }

    public function createCase(array $data): VisaCase
    {
        $user     = Auth::user();
        $agencyId = $user->agency_id;

        // Проверка лимита заявок по плану
        $agency      = Agency::findOrFail($agencyId);
        $activeCount = VisaCase::where('agency_id', $agencyId)
            ->whereNotIn('stage', ['result'])
            ->count();

        if ($activeCount >= $agency->plan->maxCases()) {
            throw ValidationException::withMessages([
                'cases' => "Active case limit reached for your plan ({$agency->plan->maxCases()} max). Upgrade to add more.",
            ]);
        }

        $data['agency_id'] = $agencyId;
        $data['stage']     = $data['stage'] ?? 'lead';

        // Авто-назначение: менеджер создаёт → ставит себя; owner может явно указать менеджера
        if (empty($data['assigned_to']) && $user->role === 'manager') {
            $data['assigned_to'] = $user->id;
        }

        // Автоматический расчёт critical_date
        if (empty($data['critical_date']) && isset($data['country_code'])) {
            // Приоритет 1: travel_date − per-visa-type данные (или fallback на portal_countries)
            if (!empty($data['travel_date'])) {
                $travelDate   = \Illuminate\Support\Carbon::parse($data['travel_date']);
                $criticalDate = isset($data['visa_type'])
                    ? $this->slaService->calculateCriticalDateFromTravelEnhanced($data['country_code'], $data['visa_type'], $travelDate)
                    : $this->slaService->calculateCriticalDateFromTravel($data['country_code'], $travelDate);
            }
            // Приоритет 2: SLA-правило (now + max_days)
            if (empty($criticalDate) && isset($data['visa_type'])) {
                $criticalDate = $this->slaService->calculateCriticalDate($data['country_code'], $data['visa_type']);
            }
            if (!empty($criticalDate)) {
                $data['critical_date'] = $criticalDate;
            }
        }

        $case = DB::transaction(function () use ($data) {
            /** @var VisaCase */
            $case = $this->repository->create($data);

            CaseStage::create([
                'case_id'    => $case->id,
                'user_id'    => Auth::id(),
                'stage'      => $data['stage'],
                'entered_at' => now(),
            ]);

            // Авто-создание чек-листа документов на основе страны + типа визы
            $this->checklistService->createForCase($case);

            return $case->load(['client', 'assignee']);
        });

        CaseCreated::dispatch($case, Auth::id());

        return $case;
    }

    /**
     * Обновить заявку. Если travel_date изменилась и critical_date не передан явно —
     * пересчитываем critical_date на основе данных посольства.
     */
    public function updateCase(string $id, array $data): VisaCase
    {
        /** @var VisaCase $case */
        $case = $this->repository->findOrFail($id);

        // Авторасчёт critical_date при изменении travel_date (per-visa-type → fallback)
        if (isset($data['travel_date']) && !array_key_exists('critical_date', $data)) {
            $travelDate   = \Illuminate\Support\Carbon::parse($data['travel_date']);
            $criticalDate = $case->visa_type
                ? $this->slaService->calculateCriticalDateFromTravelEnhanced($case->country_code, $case->visa_type, $travelDate)
                : $this->slaService->calculateCriticalDateFromTravel($case->country_code, $travelDate);
            if ($criticalDate) {
                $data['critical_date'] = $criticalDate;
            }
        }

        return $this->repository->update($id, $data);
    }

    public function moveToStage(VisaCase $case, string $newStage, ?string $notes = null): VisaCase
    {
        $previousStage = $case->stage;

        $result = DB::transaction(function () use ($case, $newStage, $notes) {
            // Закрываем текущий этап
            CaseStage::where('case_id', $case->id)
                ->whereNull('exited_at')
                ->update(['exited_at' => now()]);

            // Открываем новый этап
            $newCaseStage = CaseStage::create([
                'case_id'    => $case->id,
                'user_id'    => Auth::id(),
                'stage'      => $newStage,
                'entered_at' => now(),
                'notes'      => $notes,
            ]);

            // Устанавливаем SLA дедлайн для нового этапа
            $this->slaService->applyStageSla($newCaseStage, $case);

            $case->update(['stage' => $newStage]);

            return $case->fresh(['client', 'assignee', 'stageHistory']);
        });

        CaseStatusChanged::dispatch($result, $previousStage, $newStage, Auth::id());

        // Уведомление клиенту — email и Telegram
        if ($result->client) {
            if ($result->client->email) {
                $result->client->notify(new CaseStageChangedNotification($result, $previousStage));
            }

            if ($result->client->telegram_chat_id) {
                $this->sendTelegramStageNotification($result, $previousStage);
            }
        }

        return $result;
    }

    private function sendTelegramStageNotification(VisaCase $case, string $previousStage): void
    {
        try {
            $notification = new \App\Modules\Notification\Notifications\TelegramCaseNotification(
                $case,
                $previousStage
            );
            $case->client->notify($notification);
        } catch (\Throwable) {
            // Не даём Telegram-ошибке сломать основной флоу
        }
    }

    public function byStage(string $stage): Collection
    {
        return $this->caseRepository()->byStage($stage);
    }

    public function critical(): Collection
    {
        return $this->caseRepository()->critical();
    }
}
