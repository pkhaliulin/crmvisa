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

        // Авто-маппинг: первое назначение менеджера -> public_status = 'manager_assigned'
        if (isset($data['assigned_to']) && $data['assigned_to'] && ! $case->assigned_to) {
            $data['public_status'] = 'manager_assigned';
        }

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

            // Авто-маппинг stage -> public_status
            $updateData = ['stage' => $newStage];
            $mappedStatus = $this->mapStageToPublicStatus($newStage);
            if ($mappedStatus) {
                $updateData['public_status'] = $mappedStatus;
            }
            $case->update($updateData);

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

    /**
     * Системный переход этапа (из cron/payment callback — без Auth::id()).
     */
    public function moveToStageSystem(VisaCase $case, string $newStage, ?string $notes = null): VisaCase
    {
        $previousStage = $case->stage;

        $result = DB::transaction(function () use ($case, $newStage, $notes) {
            CaseStage::where('case_id', $case->id)
                ->whereNull('exited_at')
                ->update(['exited_at' => now()]);

            $newCaseStage = CaseStage::create([
                'case_id'    => $case->id,
                'user_id'    => null,
                'stage'      => $newStage,
                'entered_at' => now(),
                'notes'      => $notes,
            ]);

            $this->slaService->applyStageSla($newCaseStage, $case);

            // Авто-маппинг stage -> public_status
            $updateData = ['stage' => $newStage];
            $mappedStatus = $this->mapStageToPublicStatus($newStage);
            if ($mappedStatus) {
                $updateData['public_status'] = $mappedStatus;
            }
            $case->update($updateData);

            return $case->fresh(['client', 'assignee', 'stageHistory']);
        });

        CaseStatusChanged::dispatch($result, $previousStage, $newStage, null);

        return $result;
    }

    /**
     * Маппинг этапа канбана -> клиентский public_status.
     */
    private function mapStageToPublicStatus(string $stage): ?string
    {
        $map = [
            'lead'          => 'submitted',
            'qualification' => 'manager_assigned',
            'documents'     => 'document_collection',
            'doc_review'    => 'document_collection',
            'translation'   => 'translation',
            'ready'         => 'ready_for_submission',
            'review'        => 'under_review',
            'result'        => null, // зависит от результата (completed/rejected)
        ];

        return $map[$stage] ?? null;
    }

    /**
     * Проверить автопереход после загрузки документа.
     * Все обязательные документы загружены → documents → doc_review.
     */
    public function checkAutoTransitionAfterUpload(VisaCase $case): bool
    {
        if ($case->stage !== 'documents') {
            return false;
        }

        $checklist = DB::table('case_checklist')
            ->where('case_id', $case->id)
            ->whereNull('deleted_at')
            ->get(['is_required', 'status']);

        $allRequiredUploaded = $checklist
            ->where('is_required', true)
            ->every(fn ($item) => in_array($item->status, ['uploaded', 'approved', 'needs_translation']));

        if ($allRequiredUploaded && $checklist->where('is_required', true)->count() > 0) {
            $this->moveToStageSystem($case, 'doc_review', 'Все обязательные документы загружены');
            return true;
        }

        return false;
    }

    /**
     * Проверить автопереход после проверки документа менеджером.
     * Все документы проверены → doc_review → translation (если нужен) или ready.
     */
    public function checkAutoTransitionAfterReview(VisaCase $case): bool
    {
        if ($case->stage !== 'doc_review') {
            return false;
        }

        $checklist = DB::table('case_checklist')
            ->where('case_id', $case->id)
            ->whereNull('deleted_at')
            ->where('is_required', true)
            ->get(['status', 'review_status']);

        // Есть отклонённые → возвращаем в documents
        if ($checklist->contains('review_status', 'rejected')) {
            $this->moveToStageSystem($case, 'documents', 'Есть отклонённые документы — требуется перезагрузка');
            return true;
        }

        // Все проверены (approved или needs_translation)?
        $allReviewed = $checklist->every(fn ($item) =>
            in_array($item->review_status, ['approved', 'needs_translation'])
        );

        if (! $allReviewed) {
            return false;
        }

        // Есть документы на перевод?
        $needsTranslation = $checklist->contains('review_status', 'needs_translation');

        if ($needsTranslation) {
            $this->moveToStageSystem($case, 'translation', 'Документы проверены, требуется перевод');
        } else {
            $this->moveToStageSystem($case, 'ready', 'Все документы одобрены, перевод не требуется');
        }

        return true;
    }

    /**
     * Проверить автопереход после завершения перевода.
     * Все переводы готовы и проверены → translation → ready.
     */
    public function checkAutoTransitionAfterTranslation(VisaCase $case): bool
    {
        if ($case->stage !== 'translation') {
            return false;
        }

        $needTranslation = DB::table('case_checklist')
            ->where('case_id', $case->id)
            ->whereNull('deleted_at')
            ->where('review_status', 'needs_translation')
            ->get(['status']);

        $allTranslated = $needTranslation->every(fn ($item) =>
            $item->status === 'translation_approved'
        );

        if ($allTranslated && $needTranslation->count() > 0) {
            $this->moveToStageSystem($case, 'ready', 'Все переводы проверены и одобрены');
            return true;
        }

        return false;
    }

    /**
     * Завершить заявку с результатом.
     */
    public function completeCase(VisaCase $case, string $resultType, array $data): VisaCase
    {
        return DB::transaction(function () use ($case, $resultType, $data) {
            $updateData = [
                'result_type'  => $resultType,
                'result_notes' => $data['result_notes'] ?? null,
            ];

            if ($resultType === 'approved') {
                $updateData['public_status']      = 'completed';
                $updateData['visa_issued_at']      = $data['visa_issued_at'] ?? null;
                $updateData['visa_received_at']    = $data['visa_received_at'] ?? null;
                $updateData['visa_validity']        = $data['visa_validity'] ?? null;
            } else {
                $updateData['public_status']            = 'rejected';
                $updateData['rejection_reason']          = $data['rejection_reason'] ?? null;
                $updateData['can_reapply']               = $data['can_reapply'] ?? null;
                $updateData['reapply_recommendation']    = $data['reapply_recommendation'] ?? null;
            }

            $case->update($updateData);

            // Переход на этап result
            $this->moveToStage($case->fresh(), 'result', "Результат: {$resultType}");

            return $case->fresh(['client', 'assignee', 'stageHistory']);
        });
    }
}
