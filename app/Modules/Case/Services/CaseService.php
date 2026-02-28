<?php

namespace App\Modules\Case\Services;

use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Repositories\CaseRepository;
use App\Support\Abstracts\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CaseService extends BaseService
{
    public function __construct(CaseRepository $repository)
    {
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
        return DB::transaction(function () use ($case, $newStage, $notes) {
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
