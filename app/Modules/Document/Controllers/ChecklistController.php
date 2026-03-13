<?php

namespace App\Modules\Document\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use App\Modules\Document\Events\DocumentUploaded;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\Document\Services\CrossDocumentValidatorService;
use App\Modules\Document\Services\DocumentAiAnalyzerService;
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
     * Удалить кастомный слот
     */
    public function destroy(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $this->authorizeCase($request, $caseId);
        $item = $this->authorizeItem($request, $caseId, $itemId);

        if ($item->requirement_id || $item->country_requirement_id) {
            return ApiResponse::error('Cannot delete standard checklist items', null, 403);
        }

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

        // Сохраняем результат анализа в чек-лист
        $item->update([
            'ai_analysis'   => $result->toArray(),
            'ai_analyzed_at' => now(),
            'ai_confidence'  => $result->confidence,
        ]);

        return ApiResponse::success($result->toArray(), 'AI-анализ завершён');
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
}
