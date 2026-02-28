<?php

namespace App\Modules\Case\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Repositories\CaseRepository;
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

        // Автоматический расчёт critical_date через SLA-движок
        if (empty($data['critical_date']) && isset($data['country_code'], $data['visa_type'])) {
            $criticalDate = $this->slaService->calculateCriticalDate($data['country_code'], $data['visa_type']);
            if ($criticalDate) {
                $data['critical_date'] = $criticalDate;
            }
        }

        return DB::transaction(function () use ($data) {
            /** @var VisaCase */
            $case = $this->repository->create($data);

            CaseStage::create([
                'case_id'    => $case->id,
                'user_id'    => Auth::id(),
                'stage'      => $data['stage'],
                'entered_at' => now(),
            ]);

            return $case->load(['client', 'assignee']);
        });
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
            CaseStage::create([
                'case_id'    => $case->id,
                'user_id'    => Auth::id(),
                'stage'      => $newStage,
                'entered_at' => now(),
                'notes'      => $notes,
            ]);

            $case->update(['stage' => $newStage]);

            return $case->fresh(['client', 'assignee', 'stageHistory']);
        });

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
