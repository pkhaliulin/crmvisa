<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use App\Modules\Document\DTOs\DocumentAnalysisResult;
use App\Modules\Document\Events\DocumentUploaded;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\Document\Services\CrossDocumentValidatorService;
use App\Modules\Document\Services\DocumentAiAnalyzerService;
use App\Modules\Notification\Notifications\BusinessNotification;
use App\Modules\Notification\Services\NotificationService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function __construct(
        private readonly ChecklistService $service,
        private readonly CaseService $caseService,
        private readonly DocumentAiAnalyzerService $aiAnalyzer,
        private readonly CrossDocumentValidatorService $crossValidator,
    ) {}

    /**
     * GET /api/v1/cases/{caseId}/checklist
     * Чек-лист документов для заявки
     */
    public function index(Request $request, string $caseId): JsonResponse
    {
        $this->authorizeCase($request, $caseId);

        $data = $this->service->getForCase($caseId);

        return ApiResponse::success($data);
    }

    /**
     * POST /api/v1/cases/{caseId}/checklist/{itemId}/upload
     * Загрузить файл в слот чек-листа
     */
    public function upload(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,tiff,bmp', new \App\Rules\SafeFileName],
        ]);

        $result = $this->service->uploadToSlot($item, $request->file('file'), $case);

        // Событие: документ загружен
        if ($result->document) {
            DocumentUploaded::dispatch($result->document, $request->user()->id, 'crm');
        }

        // Авто-переход: все обязательные документы загружены → doc_review
        $this->caseService->checkAutoTransitionAfterUpload($case->fresh());

        return ApiResponse::success($result, 'Document uploaded');
    }

    /**
     * PATCH /api/v1/cases/{caseId}/checklist/{itemId}/review
     * Одобрить / отклонить документ (менеджер)
     */
    public function review(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        $validated = $request->validate([
            'status'            => ['required', 'in:approved,rejected,needs_translation'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'translation_pages' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->service->reviewSlot(
            $item,
            $validated['status'],
            $validated['notes'] ?? null,
            $validated['translation_pages'] ?? null
        );

        // Проверить автопереход
        $this->caseService->checkAutoTransitionAfterReview($case->fresh());

        return ApiResponse::success($result, 'Status updated');
    }

    /**
     * POST /api/v1/cases/{caseId}/checklist/{itemId}/upload-translation
     * Загрузить перевод документа (переводчик/менеджер).
     */
    public function uploadTranslation(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        if ($item->review_status !== 'needs_translation') {
            return ApiResponse::error('Документ не отмечен как требующий перевод', null, 422);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,tiff,bmp', new \App\Rules\SafeFileName],
        ]);

        $result = $this->service->uploadTranslation($item, $request->file('file'), $case);

        return ApiResponse::success($result, 'Translation uploaded');
    }

    /**
     * PATCH /api/v1/cases/{caseId}/checklist/{itemId}/approve-translation
     * Менеджер одобряет перевод.
     */
    public function approveTranslation(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        if ($item->status !== 'translated') {
            return ApiResponse::error('Перевод ещё не загружен', null, 422);
        }

        $result = $this->service->approveTranslation($item);

        // Проверить автопереход
        $this->caseService->checkAutoTransitionAfterTranslation($case->fresh());

        return ApiResponse::success($result, 'Translation approved');
    }

    /**
     * POST /api/v1/cases/{caseId}/checklist
     * Добавить кастомный слот вручную (менеджер)
     */
    public function store(Request $request, string $caseId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_required' => ['boolean'],
        ]);

        $item = $this->service->addCustomSlot(
            $case,
            $validated['name'],
            $validated['description'] ?? null,
            $validated['is_required'] ?? false
        );

        return ApiResponse::created($item, 'Checklist item added');
    }

    /**
     * DELETE /api/v1/cases/{caseId}/checklist/{itemId}
     * Удалить слот из чек-листа (любой — кастомный или стандартный).
     * Агентство регулирует какие документы нужны для конкретной заявки.
     */
    public function destroy(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        $this->service->removeSlot($item);

        return ApiResponse::success(null, 'Checklist item deleted');
    }

    /**
     * PATCH /api/v1/cases/{caseId}/checklist/{itemId}/check
     * Отметить / снять отметку checkbox-слота
     */
    public function check(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        if ($item->type !== 'checkbox') {
            return ApiResponse::error('This item requires file upload, not checkbox');
        }

        $validated = $request->validate(['checked' => ['required', 'boolean']]);
        $result = $this->service->toggleCheck($item, $validated['checked']);

        return ApiResponse::success($result, 'Updated');
    }

    /**
     * POST /api/v1/cases/{caseId}/checklist/{itemId}/ai-analyze
     * AI-анализ загруженного документа: извлечение данных, валидация, оценка рисков
     */
    public function analyzeDocument(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        // Проверяем что документ загружен
        if (!$item->document_id || !$item->document) {
            return ApiResponse::error('Документ не загружен в этот слот', null, 422);
        }

        // Получаем шаблон документа через связь countryRequirement -> documentTemplate
        $template = $item->countryRequirement?->template;

        if (!$template) {
            return ApiResponse::error('Шаблон документа не найден', null, 422);
        }

        // Путь к файлу документа
        $filePath = $item->document->file_path;

        // Контекст кейса для AI-анализа (даты поездки, страна, тип визы)
        $context = [
            'travel_date'  => $case->travel_date?->format('Y-m-d'),
            'country_code' => $case->country_code,
            'visa_type'    => $case->visa_type,
        ];

        // Вызов AI-анализатора с контекстом для логирования расходов
        $this->aiAnalyzer->setContext($request->user()->agency_id, $caseId, $request->user()->id);
        $result = $this->aiAnalyzer->analyze($filePath, $template, $context);

        // Проверка: документ принадлежит правильному человеку?
        $personMismatch = $this->checkDocumentBelongsToPerson($item, $case, $result);

        $analysisData = $result->toArray();
        if ($personMismatch) {
            $analysisData['person_mismatch'] = $personMismatch;
        }

        // Сохраняем результат анализа в чек-лист
        $item->update([
            'ai_analysis'   => $analysisData,
            'ai_analyzed_at' => now(),
            'ai_confidence'  => $result->confidence,
        ]);

        // Уведомить агента и клиента о неправильном документе
        if ($personMismatch) {
            $this->notifyPersonMismatch($case, $item, $personMismatch);
        }

        return ApiResponse::success($analysisData, $personMismatch
            ? 'AI-анализ завершён. Внимание: документ может принадлежать другому человеку!'
            : 'AI-анализ завершён'
        );
    }

    /**
     * GET /api/v1/cases/{caseId}/ai-risk
     * Оценка рисков кейса на основе AI-анализов всех документов
     */
    public function caseRiskScore(Request $request, string $caseId): JsonResponse
    {
        $case = $this->authorizeCase($request, $caseId);

        // Собираем все элементы чек-листа с AI-анализами
        $items = CaseChecklist::where('case_id', $caseId)
            ->where('agency_id', $request->user()->agency_id)
            ->with(['countryRequirement.template'])
            ->get();

        // Формируем массив данных документов для кросс-валидации и расчёта рисков
        $documentsData = [];
        $documentsAnalysis = [];

        foreach ($items as $item) {
            $slug = $item->countryRequirement?->template?->slug ?? $item->name;
            $analysis = $item->ai_analysis;

            // Данные для кросс-валидации (извлечённые поля)
            if ($analysis && isset($analysis['extracted_data'])) {
                $documentsData[$slug] = [
                    'extracted' => $analysis['extracted_data'],
                ];
            }

            // Данные для расчёта рисков
            $documentsAnalysis[$slug] = [
                'status'       => $item->document_id ? 'uploaded' : 'missing',
                'required'     => $item->is_required,
                'confidence'   => $item->ai_confidence,
                'stop_factors' => $analysis['stop_factors'] ?? [],
                'weight'       => $item->countryRequirement?->template?->ai_enabled ? 2 : 1,
            ];
        }

        // Кросс-валидация: проверка согласованности данных между документами
        $mismatches = $this->crossValidator->validate($case, $documentsData);

        // Расчёт общего риска кейса
        $riskResult = $this->crossValidator->calculateCaseRisk($case, $documentsAnalysis);

        // Добавляем несоответствия к результату
        $riskResult['mismatches'] = $mismatches;

        return ApiResponse::success($riskResult);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authorizeCase(Request $request, string $caseId): VisaCase
    {
        return VisaCase::where('id', $caseId)
                       ->where('agency_id', $request->user()->agency_id)
                       ->firstOrFail();
    }

    private function authorizeItem(Request $request, string $caseId, string $itemId): CaseChecklist
    {
        return CaseChecklist::where('id', $itemId)
                             ->where('case_id', $caseId)
                             ->where('agency_id', $request->user()->agency_id)
                             ->firstOrFail();
    }

    /**
     * Проверить, что документ принадлежит правильному человеку.
     * Сравнивает имя из AI-извлечения с ожидаемым именем (клиент или член семьи).
     */
    private function checkDocumentBelongsToPerson(
        CaseChecklist $item,
        VisaCase $case,
        DocumentAnalysisResult $result,
    ): ?array {
        $extracted = $result->extractedData;
        if (empty($extracted)) {
            return null;
        }

        // Имя из документа (AI)
        $docSurname = $extracted['surname'] ?? '';
        $docGivenNames = $extracted['given_names'] ?? '';
        $docFullName = $extracted['full_name'] ?? '';
        $docName = $docFullName ?: trim("{$docSurname} {$docGivenNames}");

        if (! $docName) {
            // Попробовать другие поля (child_name, spouse1_name, etc.)
            $docName = $extracted['child_name']
                ?? $extracted['student_name']
                ?? $extracted['employee_name']
                ?? $extracted['account_holder']
                ?? $extracted['applicant_name']
                ?? '';
        }

        if (! $docName) {
            return null; // AI не извлёк имя — не можем проверить
        }

        $normalizedDocName = $this->normalizeName($docName);

        // Ожидаемое имя
        if ($item->family_member_id) {
            // Документ для члена семьи
            $item->loadMissing('familyMember');
            $expectedName = $item->familyMember?->name ?? '';
        } else {
            // Документ для основного заявителя
            $case->loadMissing('client');
            $expectedName = $case->client?->name ?? '';
        }

        if (! $expectedName) {
            return null;
        }

        $normalizedExpected = $this->normalizeName($expectedName);

        // Сравнение: ищем хотя бы частичное совпадение фамилии
        if ($normalizedDocName === $normalizedExpected) {
            return null; // Совпадает полностью
        }

        // Разбиваем на слова и проверяем пересечение
        $docWords = array_filter(explode(' ', $normalizedDocName));
        $expectedWords = array_filter(explode(' ', $normalizedExpected));
        $common = array_intersect($docWords, $expectedWords);

        // Если хотя бы фамилия совпадает — считаем OK (у семьи одна фамилия)
        // Но если ни одного общего слова — точно не тот человек
        if (count($common) >= 1) {
            // Одно общее слово (фамилия). Проверим, не совпадают ли остальные
            // Если all words match except one — likely same person with different name format
            $diffCount = count(array_diff($docWords, $expectedWords)) + count(array_diff($expectedWords, $docWords));
            if ($diffCount <= 2) {
                return null; // Достаточно похоже
            }
        }

        // Нет совпадений или слишком большая разница — несоответствие
        $expectedLabel = $item->family_member_id
            ? ($item->familyMember?->name ?? 'член семьи')
            : ($case->client?->name ?? 'заявитель');

        return [
            'expected_person' => $expectedLabel,
            'document_person' => $docName,
            'severity'        => 'critical',
            'message'         => "Документ содержит данные \"{$docName}\", но загружен в слот \"{$expectedLabel}\"",
        ];
    }

    /**
     * Уведомить агента и клиента о загрузке документа не того человека.
     */
    private function notifyPersonMismatch(VisaCase $case, CaseChecklist $item, array $mismatch): void
    {
        $case->loadMissing(['client', 'agency']);
        $docName = $item->name ?? 'Документ';

        // Уведомление агенту
        try {
            $notification = new BusinessNotification('document.person_mismatch', [
                'subject' => "Неверный документ — {$case->case_number}",
                'message' => "Документ \"{$docName}\" содержит данные \"{$mismatch['document_person']}\", "
                    . "но загружен для \"{$mismatch['expected_person']}\".\n"
                    . "Вероятно, загружен документ другого человека. Проверьте и замените.",
                'details' => [$mismatch['message']],
                'case_id' => $case->id,
            ]);

            app(NotificationService::class)->dispatch(
                agencyId: $case->agency_id,
                eventType: 'document.person_mismatch',
                notification: $notification,
                context: ['case' => $case, 'assigned_to' => $case->assigned_to],
            );
        } catch (\Throwable $e) {
            \Log::warning('Failed to notify agent about person mismatch', ['error' => $e->getMessage()]);
        }

        // Уведомление клиенту
        if ($case->client) {
            try {
                $clientNotification = new BusinessNotification('document.person_mismatch', [
                    'subject' => 'Загружен документ другого человека',
                    'message' => "Документ \"{$docName}\" для \"{$mismatch['expected_person']}\" "
                        . "содержит данные \"{$mismatch['document_person']}\".\n"
                        . "Пожалуйста, загрузите правильный документ.",
                    'details' => [$mismatch['message']],
                    'case_id' => $case->id,
                ]);

                app(NotificationService::class)->dispatchToClient(
                    $case->client,
                    $clientNotification,
                    $case->agency,
                    ['database', 'telegram'],
                );
            } catch (\Throwable $e) {
                \Log::warning('Failed to notify client about person mismatch', ['error' => $e->getMessage()]);
            }
        }
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }
}
