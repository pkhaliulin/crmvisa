<?php

namespace App\Modules\Case\Services;

use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Repositories\CaseRepository;
use App\Modules\Notification\Notifications\CaseStageChangedNotification;
use App\Modules\Workflow\Services\SlaService;
use App\Support\Abstracts\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $data['stage'] = $data['stage'] ?? 'lead';

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

        // Уведомление клиенту (если есть email)
        if ($result->client && $result->client->email) {
            $result->client->notify(new CaseStageChangedNotification($result, $previousStage));
        }

        return $result;
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
