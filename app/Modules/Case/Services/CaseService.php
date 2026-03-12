<?php

namespace App\Modules\Case\Services;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Events\CaseAssigned;
use App\Modules\Case\Events\CaseCreated;
use App\Modules\Case\Events\CaseStatusChanged;
use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Repositories\CaseRepository;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\Notification\Notifications\CaseStageChangedNotification;
use App\Modules\Notification\Notifications\TelegramCaseNotification;
use App\Modules\Notification\Services\NotificationService;
use App\Modules\Payment\Models\ClientPayment;
use App\Modules\Workflow\Services\SlaService;
use App\Support\Abstracts\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaseService extends BaseService
{
    /**
     * Допустимые переходы между этапами канбана.
     * Ключ — текущий этап, значение — массив разрешённых следующих этапов.
     */
    const ALLOWED_TRANSITIONS = [
        'lead'          => ['qualification'],
        'qualification' => ['documents'],
        'documents'     => ['qualification', 'doc_review'],
        'doc_review'    => ['documents', 'translation', 'ready'],
        'translation'   => ['doc_review', 'ready'],
        'ready'         => ['translation', 'review'],
        'review'        => ['ready', 'result'],
        'result'        => ['lead'], // повторная подача при отказе
    ];

    public function __construct(
        CaseRepository $repository,
        private SlaService $slaService,
        private ChecklistService $checklistService,
        private NotificationService $notificationService,
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

        // Если менеджер назначен — сразу в qualification (не задерживаемся в lead)
        if (!empty($data['assigned_to']) && ($data['stage'] ?? 'lead') === 'lead') {
            $data['stage'] = 'qualification';
            $data['public_status'] = 'manager_assigned';
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

        // Авто-переход: назначение менеджера на этапе lead → автоматически в qualification
        $shouldMoveToQualification = isset($data['assigned_to']) && $data['assigned_to']
            && ! $case->assigned_to && $case->stage === 'lead';

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

        // Отслеживаем первое назначение менеджера
        $isNewAssignment = isset($data['assigned_to']) && $data['assigned_to'] && !$case->assigned_to;

        // Оптимистичная блокировка: если фронт передал lock_version — используем
        $lockVersion = $data['lock_version'] ?? null;
        unset($data['lock_version']);

        if ($lockVersion !== null) {
            $case->optimisticUpdate($data, (int) $lockVersion);
            $case = $case->fresh(['client', 'assignee']);
        } else {
            $case = $this->repository->update($id, $data);
        }

        // Событие: менеджер назначен
        if ($isNewAssignment) {
            CaseAssigned::dispatch($case, $data['assigned_to'] ?? $case->assigned_to, Auth::id());
        }

        // После сохранения assigned_to — перемещаем из lead в qualification
        if ($shouldMoveToQualification) {
            $case = $this->moveToStage($case, 'qualification', 'Автопереход: назначен менеджер');
        }

        return $case;
    }

    public function moveToStage(VisaCase $case, string $newStage, ?string $notes = null): VisaCase
    {
        // Валидация: назначен ли менеджер
        if (! $case->assigned_to) {
            throw ValidationException::withMessages([
                'assigned_to' => ['Невозможно переместить заявку без назначенного менеджера. Сначала назначьте ответственного.'],
            ]);
        }

        // Валидация: разрешён ли переход (предварительная, до блокировки)
        $allowed = self::ALLOWED_TRANSITIONS[$case->stage] ?? [];
        if (! in_array($newStage, $allowed)) {
            throw ValidationException::withMessages([
                'stage' => ["Переход из «{$case->stage}» в «{$newStage}» невозможен."],
            ]);
        }

        // Повторная подача: result -> lead только для отказанных заявок
        if ($case->stage === 'result' && $newStage === 'lead') {
            if ($case->public_status !== 'rejected') {
                throw ValidationException::withMessages([
                    'stage' => ['Повторная подача возможна только для отказанных заявок.'],
                ]);
            }
        }

        // Внешние лиды (не из VisaBor): qualification -> documents только после оплаты
        if ($case->stage === 'qualification' && $newStage === 'documents') {
            if ($this->isExternalLead($case) && $case->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'payment_status' => ['Невозможно перейти к сбору документов без оплаты. Дождитесь оплаты от клиента.'],
                ]);
            }
        }

        $result = DB::transaction(function () use ($case, $newStage, $notes) {
            // SELECT FOR UPDATE — предотвращаем race condition при одновременных перемещениях
            $lockedCase = VisaCase::where('id', $case->id)->lockForUpdate()->first();
            if (! $lockedCase) {
                throw ValidationException::withMessages([
                    'stage' => ['Заявка не найдена.'],
                ]);
            }

            $previousStage = $lockedCase->stage;

            // Повторная валидация после блокировки (stage мог измениться)
            $allowed = self::ALLOWED_TRANSITIONS[$previousStage] ?? [];
            if (! in_array($newStage, $allowed)) {
                throw ValidationException::withMessages([
                    'stage' => ["Переход из «{$previousStage}» в «{$newStage}» невозможен."],
                ]);
            }

            // Закрываем текущий этап
            CaseStage::where('case_id', $lockedCase->id)
                ->whereNull('exited_at')
                ->update(['exited_at' => now()]);

            // Открываем новый этап
            $newCaseStage = CaseStage::create([
                'case_id'    => $lockedCase->id,
                'user_id'    => Auth::id(),
                'stage'      => $newStage,
                'entered_at' => now(),
                'notes'      => $notes,
            ]);

            // Устанавливаем SLA дедлайн для нового этапа
            $this->slaService->applyStageSla($newCaseStage, $lockedCase);

            // Авто-маппинг stage -> public_status
            $updateData = ['stage' => $newStage];
            $stageOrder = array_flip(array_keys(self::ALLOWED_TRANSITIONS));
            $isForward = ($stageOrder[$newStage] ?? 0) >= ($stageOrder[$previousStage] ?? 0);
            if ($isForward) {
                $mappedStatus = $this->mapStageToPublicStatus($newStage);
                if ($mappedStatus) {
                    $updateData['public_status'] = $mappedStatus;
                }
            } else {
                // При откате назад — пересчитать public_status чтобы не было рассинхрона
                $mappedStatus = $this->mapStageToPublicStatus($newStage);
                if ($mappedStatus) {
                    $updateData['public_status'] = $mappedStatus;
                }
            }

            $lockedCase->update($updateData);

            return [$lockedCase->fresh(['client', 'assignee', 'stageHistory']), $previousStage];
        });

        [$freshCase, $previousStage] = $result;

        CaseStatusChanged::dispatch($freshCase, $previousStage, $newStage, Auth::id());

        // Уведомление клиенту через единую систему (бренд определяется автоматически)
        if ($freshCase->client) {
            $this->notifyClientAboutStageChange($freshCase, $previousStage);
        }

        return $freshCase;
    }

    /**
     * Уведомить клиента о смене этапа через NotificationService.
     * Бренд: marketplace → VisaBor, direct → имя агентства.
     */
    private function notifyClientAboutStageChange(VisaCase $case, string $previousStage): void
    {
        try {
            // Email + database уведомление
            $this->notificationService->dispatchToClient(
                $case->client,
                new CaseStageChangedNotification($case, $previousStage),
                $case->agency,
                ['database', 'email'],
            );

            // Telegram уведомление (отдельный notification-класс с rich formatting)
            if ($case->client->telegram_chat_id) {
                $this->notificationService->dispatchToClient(
                    $case->client,
                    new TelegramCaseNotification($case, $previousStage),
                    $case->agency,
                    ['telegram'],
                );
            }
        } catch (\Throwable) {
            // Не даём ошибке уведомления сломать основной флоу
        }
    }

    public function byStage(string $stage): Collection
    {
        return $this->caseRepository()->byStage($stage);
    }

    public function critical(string $agencyId): Collection
    {
        return $this->caseRepository()->critical($agencyId);
    }

    /**
     * Системный переход этапа (из cron/payment callback — без Auth::id()).
     */
    public function moveToStageSystem(VisaCase $case, string $newStage, ?string $notes = null): VisaCase
    {
        // Предварительная проверка
        if ($case->stage === 'result') {
            throw ValidationException::withMessages([
                'stage' => ['Заявка уже завершена, переход невозможен.'],
            ]);
        }

        $allowed = self::ALLOWED_TRANSITIONS[$case->stage] ?? [];
        if (!in_array($newStage, $allowed)) {
            throw ValidationException::withMessages([
                'stage' => ["Системный переход из «{$case->stage}» в «{$newStage}» невозможен."],
            ]);
        }

        $result = DB::transaction(function () use ($case, $newStage, $notes) {
            // SELECT FOR UPDATE — предотвращаем race condition
            $lockedCase = VisaCase::where('id', $case->id)->lockForUpdate()->first();
            if (! $lockedCase) {
                throw ValidationException::withMessages([
                    'stage' => ['Заявка не найдена.'],
                ]);
            }

            $previousStage = $lockedCase->stage;

            // Повторная валидация после блокировки
            $allowed = self::ALLOWED_TRANSITIONS[$previousStage] ?? [];
            if (!in_array($newStage, $allowed)) {
                throw ValidationException::withMessages([
                    'stage' => ["Системный переход из «{$previousStage}» в «{$newStage}» невозможен."],
                ]);
            }

            CaseStage::where('case_id', $lockedCase->id)
                ->whereNull('exited_at')
                ->update(['exited_at' => now()]);

            $newCaseStage = CaseStage::create([
                'case_id'    => $lockedCase->id,
                'user_id'    => null,
                'stage'      => $newStage,
                'entered_at' => now(),
                'notes'      => $notes,
            ]);

            $this->slaService->applyStageSla($newCaseStage, $lockedCase);

            // Маппинг stage -> public_status (и вперёд, и назад — чтобы не было рассинхрона)
            $updateData = ['stage' => $newStage];
            $mappedStatus = $this->mapStageToPublicStatus($newStage);
            if ($mappedStatus) {
                $updateData['public_status'] = $mappedStatus;
            }
            $lockedCase->update($updateData);

            return [$lockedCase->fresh(['client', 'assignee', 'stageHistory']), $previousStage];
        });

        [$freshCase, $previousStage] = $result;

        CaseStatusChanged::dispatch($freshCase, $previousStage, $newStage, null);

        return $freshCase;
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
            'doc_review'    => 'document_review',
            'translation'   => 'translation',
            'ready'         => 'ready_for_submission',
            'review'        => 'under_review',
            'result'        => null, // public_status устанавливается в completeCase() (completed/rejected)
        ];

        return $map[$stage] ?? null;
    }

    /**
     * Является ли заявка внешним лидом (не из маркетплейса VisaBor).
     * Внешние лиды: API, Instagram, Telegram, виджет — client.source !== 'marketplace'.
     * Для них оплата происходит ПОСЛЕ квалификации, а не ДО попадания на канбан.
     */
    private function isExternalLead(VisaCase $case): bool
    {
        // lead_source заполнен = пришёл через API/внешний канал
        if ($case->lead_source && $case->lead_source !== 'visabor') {
            return true;
        }

        // client.source !== 'marketplace' = не через маркетплейс VisaBor
        $client = $case->client;
        if ($client && !in_array($client->source, ['marketplace', 'group_invite'])) {
            return true;
        }

        return false;
    }

    /**
     * Проверить автопереход после загрузки документа.
     * Все обязательные документы (и клиента, и агентства) загружены → documents → doc_review.
     * SELECT FOR UPDATE на case для предотвращения race condition при параллельных загрузках.
     */
    public function checkAutoTransitionAfterUpload(VisaCase $case): bool
    {
        if ($case->stage !== 'documents') {
            return false;
        }

        return DB::transaction(function () use ($case) {
            // Блокируем case для предотвращения race condition
            $lockedCase = VisaCase::where('id', $case->id)->lockForUpdate()->first();
            if (!$lockedCase || $lockedCase->stage !== 'documents') {
                return false;
            }

            $checklist = DB::table('case_checklist')
                ->where('case_id', $case->id)
                ->whereNull('deleted_at')
                ->get(['is_required', 'status', 'responsibility']);

            $allRequired = $checklist->where('is_required', true);

            if ($allRequired->isEmpty()) {
                return false;
            }

            $allUploaded = $allRequired
                ->every(fn ($item) => in_array($item->status, ['uploaded', 'approved', 'needs_translation']));

            if ($allUploaded) {
                $this->moveToStageSystem($lockedCase, 'doc_review', 'Все обязательные документы загружены');
                return true;
            }

            return false;
        });
    }

    /**
     * Проверить автопереход после проверки документа менеджером.
     * Все документы проверены → doc_review → translation (если нужен) или ready.
     * SELECT FOR UPDATE на case для предотвращения race condition.
     */
    public function checkAutoTransitionAfterReview(VisaCase $case): bool
    {
        if ($case->stage !== 'doc_review') {
            return false;
        }

        return DB::transaction(function () use ($case) {
            $lockedCase = VisaCase::where('id', $case->id)->lockForUpdate()->first();
            if (!$lockedCase || $lockedCase->stage !== 'doc_review') {
                return false;
            }

            $checklist = DB::table('case_checklist')
                ->where('case_id', $lockedCase->id)
                ->whereNull('deleted_at')
                ->where('is_required', true)
                ->get(['status', 'review_status']);

            // Есть отклонённые → возвращаем в documents
            if ($checklist->contains('review_status', 'rejected')) {
                $this->moveToStageSystem($lockedCase, 'documents', 'Есть отклонённые документы — требуется перезагрузка');
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
                $this->moveToStageSystem($lockedCase, 'translation', 'Документы проверены, требуется перевод');
            } else {
                $this->moveToStageSystem($lockedCase, 'ready', 'Все документы одобрены, перевод не требуется');
            }

            return true;
        });
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
        $result = DB::transaction(function () use ($case, $resultType, $data) {
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

        // Уведомление клиенту о результате (через единую систему)
        if ($result->client) {
            $eventType = $resultType === 'approved' ? 'case.completed' : 'case.rejected';
            $clientName = $result->client->name;
            $message = $resultType === 'approved'
                ? "Ваша виза одобрена! Заявка #{$result->case_number}"
                : "К сожалению, в визе отказано. Заявка #{$result->case_number}";

            $this->notificationService->dispatchToClient(
                $result->client,
                new \App\Modules\Notification\Notifications\BusinessNotification($eventType, [
                    'case_id'      => $result->id,
                    'case_number'  => $result->case_number,
                    'client_name'  => $clientName,
                    'result_type'  => $resultType,
                    'country_code' => $result->country_code,
                    'message'      => $message,
                    'sms'          => $message,
                ]),
                $result->agency,
                ['database', 'email', 'telegram', 'sms'],
            );
        }

        return $result;
    }

    /**
     * Отменить заявку. Допускается на этапах до подачи в посольство (до review).
     */
    public function cancelCase(VisaCase $case, ?string $reason = null): VisaCase
    {
        $nonCancellable = ['review', 'result'];
        if (in_array($case->stage, $nonCancellable)) {
            throw ValidationException::withMessages([
                'stage' => ['Отменить заявку на этапе «' . ($case->stage === 'review' ? 'Рассмотрение' : 'Результат') . '» невозможно.'],
            ]);
        }

        if ($case->public_status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => ['Заявка уже отменена.'],
            ]);
        }

        return DB::transaction(function () use ($case, $reason) {
            CaseStage::where('case_id', $case->id)
                ->whereNull('exited_at')
                ->update(['exited_at' => now()]);

            // Отменить все pending платежи по этой заявке (#8)
            ClientPayment::where('case_id', $case->id)
                ->where('status', 'pending')
                ->each(function (ClientPayment $payment) {
                    $payment->update([
                        'status'   => 'cancelled',
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'cancelled_reason' => 'case_cancelled',
                            'cancelled_at'     => now()->toDateTimeString(),
                        ]),
                    ]);
                });

            $case->update([
                'public_status'  => 'cancelled',
                'payment_status' => $case->payment_status === 'paid' ? 'paid' : 'cancelled',
                'notes'          => $case->notes
                    ? $case->notes . "\n\nОтмена: " . ($reason ?? 'без причины')
                    : 'Отмена: ' . ($reason ?? 'без причины'),
            ]);

            return $case->fresh(['client', 'assignee', 'stageHistory']);
        });
    }
}
