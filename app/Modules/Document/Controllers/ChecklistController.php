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
            $item->loadMissing('familyMember');
            $expectedName = $item->familyMember?->name ?? '';
        } else {
            $case->loadMissing('client');
            $expectedName = $case->client?->name ?? '';
        }

        if (! $expectedName) {
            return null;
        }

        $normalizedExpected = $this->normalizeName($expectedName);

        // Прямое совпадение
        if ($normalizedDocName === $normalizedExpected) {
            return null;
        }

        // Нормализация с учётом узбекской латиницы (X=KH, O'=O, G'=GH и т.д.)
        $translitDoc = $this->normalizeUzbekLatin($normalizedDocName);
        $translitExpected = $this->normalizeUzbekLatin($normalizedExpected);

        if ($translitDoc === $translitExpected) {
            return null; // Тот же человек, разная транслитерация
        }

        // Пословное сравнение (с учётом транслитерации)
        $docWords = array_filter(explode(' ', $translitDoc));
        $expectedWords = array_filter(explode(' ', $translitExpected));
        $common = array_intersect($docWords, $expectedWords);

        if (count($common) >= 1) {
            $diffCount = count(array_diff($docWords, $expectedWords)) + count(array_diff($expectedWords, $docWords));
            if ($diffCount <= 2) {
                // Совпадает после транслитерации — информационное предупреждение, не ошибка
                $expectedLabel = $item->family_member_id
                    ? ($item->familyMember?->name ?? 'член семьи')
                    : ($case->client?->name ?? 'заявитель');

                return [
                    'expected_person' => $expectedLabel,
                    'document_person' => $docName,
                    'severity'        => 'info',
                    'message'         => "Различия в написании ФИО: \"{$docName}\" / \"{$expectedLabel}\" — вероятно, разная транслитерация (узб./англ. латиница)",
                ];
            }
        }

        // Нет совпадений — реальное несоответствие
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

    /**
     * Нормализация имени с учётом различий узбекской и международной латиницы.
     *
     * Узбекский ID (внутренний паспорт) использует узбекскую латиницу,
     * а загранпаспорт — международную (ICAO). Ключевые различия:
     *
     *   Узб. латиница  →  Междунар.  →  Кириллица
     *   X              →  KH         →  Х
     *   O'             →  O          →  Ў
     *   G'             →  GH         →  Ғ
     *   SH             →  SH         →  Ш (совпадает)
     *   CH             →  CH         →  Ч (совпадает)
     *   TS             →  TS         →  Ц (совпадает)
     *   YO             →  YO         →  Ё (совпадает)
     *   '              →  (убрать)   →  ъ/ь
     */
    private function normalizeUzbekLatin(string $name): string
    {
        // Приводим всё к единой международной форме
        $replacements = [
            // Узбекская → международная (порядок важен: сначала длинные)
            'sh' => 'sh',   // совпадает, но фиксируем
            'ch' => 'ch',   // совпадает
            'kh' => 'kh',   // уже международная
            'gh' => 'gh',   // уже международная
            'x'  => 'kh',   // X → KH (узб. Х)
            "o'" => 'o',    // O' → O (узб. Ў)
            "g'" => 'gh',   // G' → GH (узб. Ғ)
            "'"  => '',     // убираем оставшиеся апострофы (ъ/ь)
            'ʻ'  => '',     // Unicode modifier letter (U+02BB)
            'ʼ'  => '',     // Unicode modifier apostrophe (U+02BC)
            '''  => '',     // правая одинарная кавычка
        ];

        $result = $name;
        foreach ($replacements as $from => $to) {
            $result = str_replace($from, $to, $result);
        }

        return $result;
    }
}
