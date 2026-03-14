<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\DTOs\PassportData;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Services\OcrService;
use App\Modules\Document\Services\TransliterationService;
use App\Modules\PublicPortal\Models\PublicUserDocument;
use App\Support\Helpers\ApiResponse;
use App\Support\Rules\ReferenceExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PublicProfileController extends Controller
{
    /**
     * GET /public/me
     * Профиль + процент заполненности.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        // Синхронизация: если паспортные поля пусты, но есть кэшированный OCR-документ — заполнить
        $this->syncProfileFromDocuments($user);

        return ApiResponse::success([
            'user'            => $user,
            'profile_percent' => $user->profileCompleteness(),
            'has_pin'         => (bool) $user->pin_hash,
        ]);
    }

    /**
     * PATCH /public/me
     * Обновить профиль (ручной ввод или после OCR).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        $data = $request->validate([
            'name'               => 'sometimes|string|max:255',
            'first_name_lat'     => 'sometimes|nullable|string|max:100',
            'last_name_lat'      => 'sometimes|nullable|string|max:100',
            'middle_name_lat'    => 'sometimes|nullable|string|max:100',
            'first_name_cyr'     => 'sometimes|nullable|string|max:100',
            'last_name_cyr'      => 'sometimes|nullable|string|max:100',
            'middle_name_cyr'    => 'sometimes|nullable|string|max:100',
            'pnfl'               => ['sometimes', 'nullable', 'string', 'regex:/^\d{14}$/'],
            'dob'                => 'sometimes|date|before:today',
            'citizenship'        => 'sometimes|string|size:2',
            'gender'             => ['sometimes', Rule::in(['M', 'F'])],
            'place_of_birth'     => 'sometimes|nullable|string|max:255',
            'passport_number'    => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{2}[0-9]{7}$/'],
            'passport_expires_at'=> 'sometimes|date|after:today',
            'passport_issue_date'=> 'sometimes|nullable|date',
            'passport_issued_by' => 'sometimes|nullable|string|max:255',
            'id_doc_type'        => ['sometimes', 'nullable', Rule::in(['id_card', 'internal_passport'])],
            'id_doc_number'      => 'sometimes|nullable|string|max:50',
            'id_doc_expires_at'  => 'sometimes|nullable|date',
            'id_doc_issue_date'  => 'sometimes|nullable|date',
            'id_doc_issued_by'   => 'sometimes|nullable|string|max:255',
            'employment_type'    => ['sometimes', new ReferenceExists('employment_type')],
            'monthly_income_usd' => 'sometimes|integer|min:0',
            'marital_status'     => ['sometimes', new ReferenceExists('marital_status')],
            'has_children'       => 'sometimes|boolean',
            'children_count'     => 'sometimes|integer|min:0|max:20',
            'has_property'       => 'sometimes|boolean',
            'has_car'            => 'sometimes|boolean',
            'has_schengen_visa'     => 'sometimes|boolean',
            'has_us_visa'           => 'sometimes|boolean',
            'had_visa_refusal'      => 'sometimes|boolean',
            'had_overstay'          => 'sometimes|boolean',
            'had_deportation'       => 'sometimes|boolean',
            'visas_obtained_count'  => 'sometimes|integer|min:0|max:50',
            'refusals_count'        => 'sometimes|integer|min:0|max:20',
            'refusal_countries'     => 'sometimes|array',
            'refusal_countries.*'   => 'string|size:2',
            'last_refusal_year'     => 'sometimes|nullable|integer|min:2000|max:2099',
            'employed_years'        => 'sometimes|integer|min:0|max:50',
            'education_level'       => ['sometimes', 'nullable', new ReferenceExists('education_level')],
            'recovery_email'        => 'sometimes|nullable|email|max:255',
        ]);

        $user->update($data);

        $freshUser = $user->fresh();

        return ApiResponse::success([
            'user'            => $freshUser,
            'profile_percent' => $freshUser->profileCompleteness(),
        ], 'Профиль обновлён');
    }

    /**
     * POST /public/me/document
     * Загрузить документ (загранпаспорт или ID) и распознать через OCR.
     * Определяет тип документа автоматически.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'file'     => ['required', 'image', 'max:10240', new \App\Rules\SafeFileName],
            'doc_type' => ['sometimes', Rule::in(['foreign_passport', 'id_card', 'internal_passport'])],
        ]);

        $user = $request->get('_public_user');
        $expectedType = $request->input('doc_type');

        $path = $request->file('file')->store("documents/{$user->id}", 'documents');
        $fullPath = Storage::disk('documents')->path($path);

        try {
            $ocrService = app(OcrService::class);
            $ocrService->setContext(null, $user->id);
            $passportData = $ocrService->extractPassport($fullPath);

            // Определить тип документа
            $detectedType = $passportData->documentType ?? $this->detectDocumentType($passportData);

            // Если ожидался определённый тип, но распознан другой — предупредить
            $typeMismatch = null;
            if ($expectedType && $detectedType && $expectedType !== $detectedType) {
                $typeMismatch = [
                    'expected' => $expectedType,
                    'detected' => $detectedType,
                ];
            }

            // Если тип не определён — ошибка
            if (!$detectedType) {
                return ApiResponse::success([
                    'ocr_status'    => 'failed',
                    'error'         => 'unknown_document',
                    'message'       => 'Не удалось определить тип документа. Загрузите фото загранпаспорта или ID-карты.',
                ]);
            }

            // Сохранить в историю документов
            $docRecord = $this->saveDocumentRecord($user, $passportData, $detectedType, $path);

            // Обновить профиль пользователя
            $extracted = $this->updateUserFromDocument($user, $passportData, $detectedType, $path);

            // Сверка с текущими данными
            $mismatches = $this->detectMismatches($user->fresh(), $extracted, $detectedType);

            // Кросс-валидация между документами
            $crossValidation = $this->crossDocumentValidation($user->fresh());

            return ApiResponse::success([
                'ocr_status'       => 'completed',
                'document_type'    => $detectedType,
                'type_mismatch'    => $typeMismatch,
                'extracted'        => $extracted,
                'mismatches'       => $mismatches,
                'cross_validation' => $crossValidation,
                'confidence'       => $passportData->confidence,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Public document OCR failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return ApiResponse::success([
                'ocr_status' => 'failed',
                'extracted'  => null,
                'mismatches' => [],
                'message'    => 'Не удалось распознать документ. Заполните данные вручную.',
            ]);
        }
    }

    /**
     * POST /public/me/passport (обратная совместимость)
     */
    public function uploadPassport(Request $request): JsonResponse
    {
        // Переименуем поле file из passport в file для совместимости
        if ($request->hasFile('passport') && !$request->hasFile('file')) {
            $request->merge(['doc_type' => 'foreign_passport']);
            $request->files->set('file', $request->file('passport'));
        }

        return $this->uploadDocument($request);
    }

    /**
     * GET /public/me/passport-data
     * Получить данные документов для профиля.
     */
    public function passportData(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        // Проверяем наличие документов в заявках
        $hasPassportInCase = $this->checkPassportInCase($user);
        $hasIdInCase = $this->checkIdInCase($user);

        // Текущие документы
        $currentPassport = $user->currentForeignPassport();
        $currentId = $user->currentIdDocument();

        // Обратная совместимость: если нет записи в documents, но есть в старых полях
        $passportExtracted = null;
        $idExtracted = null;

        if ($currentPassport) {
            $passportExtracted = $this->formatDocumentForFrontend($currentPassport);
        } elseif ($user->passport_ocr_status === 'completed' && !empty($user->passport_ocr_data)) {
            $passportExtracted = $user->passport_ocr_data;
            unset($passportExtracted['file_path'], $passportExtracted['source']);
        } elseif ($user->ocr_status === 'completed' && !empty($user->ocr_raw_data)) {
            $passportExtracted = $user->ocr_raw_data;
            unset($passportExtracted['file_path'], $passportExtracted['source']);
        }

        if ($currentId) {
            $idExtracted = $this->formatDocumentForFrontend($currentId);
        } elseif ($user->id_doc_ocr_status === 'completed' && !empty($user->id_doc_ocr_data)) {
            $idExtracted = $user->id_doc_ocr_data;
        }

        $dismissed = $user->dismissed_mismatches ?? [];

        $passportMismatches = $passportExtracted
            ? $this->detectMismatches($user, $passportExtracted, 'foreign_passport')
            : [];
        $dismissedPassport = $dismissed['foreign_passport'] ?? [];
        if ($dismissedPassport) {
            $passportMismatches = array_values(array_filter($passportMismatches, fn ($m) => !in_array($m['field'], $dismissedPassport)));
        }

        $crossValidation = ($passportExtracted && $idExtracted)
            ? $this->crossDocumentValidation($user)
            : null;

        return ApiResponse::success([
            'passport'           => [
                'extracted'  => $passportExtracted,
                'mismatches' => $passportMismatches,
                'ocr_status' => $currentPassport ? 'completed' : ($user->passport_ocr_status ?? $user->ocr_status ?? null),
            ],
            'id_document'        => [
                'extracted'  => $idExtracted,
                'ocr_status' => $currentId ? 'completed' : ($user->id_doc_ocr_status ?? null),
            ],
            'cross_validation'     => $crossValidation,
            'has_passport_in_case' => $hasPassportInCase,
            'has_id_in_case'       => $hasIdInCase,
            // Обратная совместимость
            'extracted'            => $passportExtracted,
            'mismatches'           => $passportMismatches,
        ]);
    }

    /**
     * POST /public/me/passport-from-case
     * Распознать документ из чеклисте заявки (загранпаспорт или ID-карта).
     */
    public function ocrFromCase(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');
        $requestedType = $request->input('doc_type', 'foreign_passport');

        // Паттерны поиска по типу документа
        $namePatterns = $requestedType === 'id_card'
            ? ['%удостоверен%', '%ID%', '%id.card%', '%id_card%']
            : ['%аспорт%', '%passport%'];

        $checklistItem = CaseChecklist::whereHas('document')
            ->whereIn('case_id', function ($q) use ($user) {
                $q->select('id')
                    ->from('cases')
                    ->whereIn('client_id', function ($q2) use ($user) {
                        $q2->select('id')
                            ->from('clients')
                            ->where('public_user_id', $user->id);
                    });
            })
            ->where(function ($q) use ($namePatterns) {
                foreach ($namePatterns as $pattern) {
                    $q->orWhere('name', 'like', $pattern);
                }
            })
            ->whereIn('status', ['uploaded', 'approved'])
            ->orderByDesc('updated_at')
            ->first();

        if (!$checklistItem || !$checklistItem->document) {
            return ApiResponse::error('Паспорт не найден в заявках', null, 404);
        }

        $doc = $checklistItem->document;
        $fullPath = Storage::disk('documents')->path($doc->file_path);

        if (!file_exists($fullPath)) {
            return ApiResponse::error('Файл паспорта не найден', null, 404);
        }

        // Проверить кэш: есть ли уже успешный OCR-результат для этого файла
        $cachedDoc = PublicUserDocument::where('public_user_id', $user->id)
            ->where('file_path', $doc->file_path)
            ->where('is_current', true)
            ->whereNotNull('ocr_confidence')
            ->where('ocr_confidence', '>=', 0.3)
            ->orderByDesc('created_at')
            ->first();

        if ($cachedDoc) {
            // Использовать сохранённые данные без повторного OCR
            $extracted = $this->buildExtractedFromDocRecord($cachedDoc);
            $mismatches = $this->detectMismatches($user->fresh(), $extracted, $cachedDoc->doc_type);

            return ApiResponse::success([
                'ocr_status'    => 'completed',
                'document_type' => $cachedDoc->doc_type,
                'extracted'     => $extracted,
                'mismatches'    => $mismatches,
                'cached'        => true,
            ]);
        }

        try {
            $ocrService = app(OcrService::class);
            $ocrService->setContext(null, $user->id);
            $passportData = $ocrService->extractPassport($fullPath);

            $detectedType = $passportData->documentType ?? $this->detectDocumentType($passportData);
            if (!$detectedType) $detectedType = 'foreign_passport'; // из заявки = скорее всего загранпаспорт

            $docRecord = $this->saveDocumentRecord($user, $passportData, $detectedType, $doc->file_path);
            $extracted = $this->updateUserFromDocument($user, $passportData, $detectedType, $doc->file_path);
            $mismatches = $this->detectMismatches($user->fresh(), $extracted, $detectedType);

            return ApiResponse::success([
                'ocr_status'    => 'completed',
                'document_type' => $detectedType,
                'extracted'     => $extracted,
                'mismatches'    => $mismatches,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Public passport OCR from case failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return ApiResponse::success([
                'ocr_status' => 'failed',
                'extracted'  => null,
                'mismatches' => [],
                'message'    => 'Не удалось распознать паспорт из заявки.',
            ]);
        }
    }

    /**
     * GET /public/me/documents
     * Все документы пользователя (текущие + история).
     */
    public function allDocuments(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        $documents = PublicUserDocument::where('public_user_id', $user->id)
            ->orderByDesc('is_current')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($doc) => $this->formatDocumentForFrontend($doc));

        return ApiResponse::success(['documents' => $documents]);
    }

    /**
     * POST /public/me/dismiss-mismatches
     * Сохранить отклонённые расхождения, чтобы не показывать повторно.
     */
    public function dismissMismatches(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');
        $fields = $request->validate([
            'fields'   => 'required|array',
            'fields.*' => 'string|max:50',
            'doc_type' => 'sometimes|string|max:30',
        ]);

        $docType = $fields['doc_type'] ?? 'foreign_passport';
        $dismissed = $user->dismissed_mismatches ?? [];
        $dismissed[$docType] = array_unique(array_merge($dismissed[$docType] ?? [], $fields['fields']));
        $user->update(['dismissed_mismatches' => $dismissed]);

        return ApiResponse::success(['dismissed' => $dismissed]);
    }

    /**
     * POST /public/me/documents/validate
     * Кросс-валидация между ID и загранпаспортом.
     */
    public function crossDocumentValidationEndpoint(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');
        $result = $this->crossDocumentValidation($user);

        return ApiResponse::success($result ?? ['status' => 'no_documents']);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Синхронизировать пустые поля профиля из кэшированных OCR-документов.
     * Вызывается при загрузке профиля — если OCR ранее отработал, но профиль не обновился.
     */
    private function syncProfileFromDocuments($user): void
    {
        $docs = PublicUserDocument::where('public_user_id', $user->id)
            ->where('is_current', true)
            ->whereNotNull('ocr_confidence')
            ->where('ocr_confidence', '>=', 0.3)
            ->get();

        if ($docs->isEmpty()) return;

        $update = [];

        foreach ($docs as $doc) {
            if ($doc->doc_type === 'foreign_passport') {
                if (!$user->passport_number && $doc->doc_number)        $update['passport_number']     = $doc->doc_number;
                if (!$user->passport_expires_at && $doc->expires_at)    $update['passport_expires_at'] = $doc->expires_at;
                if (!$user->passport_issue_date && $doc->issue_date)    $update['passport_issue_date'] = $doc->issue_date;
                if (!$user->passport_issued_by && $doc->issued_by)      $update['passport_issued_by']  = $doc->issued_by;
                if (!$user->passport_file_path && $doc->file_path)      $update['passport_file_path']  = $doc->file_path;
                if ($user->passport_ocr_status !== 'completed')         $update['passport_ocr_status'] = 'completed';
                if (!$user->first_name_lat && $doc->script_type === 'latin' && $doc->first_name) {
                    $update['first_name_lat'] = $doc->first_name;
                }
                if (!$user->last_name_lat && $doc->script_type === 'latin' && $doc->last_name) {
                    $update['last_name_lat'] = $doc->last_name;
                }
            } else {
                if (!$user->id_doc_number && $doc->doc_number)          $update['id_doc_number']     = $doc->doc_number;
                if (!$user->id_doc_expires_at && $doc->expires_at)      $update['id_doc_expires_at'] = $doc->expires_at;
                if (!$user->id_doc_issue_date && $doc->issue_date)      $update['id_doc_issue_date'] = $doc->issue_date;
                if (!$user->id_doc_issued_by && $doc->issued_by)        $update['id_doc_issued_by']  = $doc->issued_by;
                if (!$user->id_doc_type)                                $update['id_doc_type']       = $doc->doc_type;
                if ($user->id_doc_ocr_status !== 'completed')           $update['id_doc_ocr_status'] = 'completed';
                if (!$user->first_name_cyr && $doc->script_type === 'cyrillic' && $doc->first_name) {
                    $update['first_name_cyr'] = $doc->first_name;
                }
                if (!$user->last_name_cyr && $doc->script_type === 'cyrillic' && $doc->last_name) {
                    $update['last_name_cyr'] = $doc->last_name;
                }
            }

            // Общие поля
            if (!$user->dob && $doc->dob)                $update['dob']            = $doc->dob;
            if (!$user->gender && $doc->gender)          $update['gender']         = $doc->gender;
            if (!$user->citizenship && $doc->nationality) $update['citizenship']   = $this->iso3ToIso2($doc->nationality);
            if (!$user->place_of_birth && $doc->place_of_birth) $update['place_of_birth'] = $doc->place_of_birth;
            if (!$user->pnfl && $doc->pnfl && preg_match('/^\d{14}$/', $doc->pnfl)) {
                $update['pnfl'] = $doc->pnfl;
            }
        }

        // Дополнить из AI-анализа документов в заявках (case_checklist)
        $this->syncFromCaseAiAnalysis($user, $update);

        if (!empty($update)) {
            $user->update($update);
            $user->refresh();
        }
    }

    /**
     * Подтянуть данные удостоверения личности и загранпаспорта из AI-анализа в заявках.
     * Правило: case_checklist.ai_analysis — авторитетный источник данных документов.
     */
    private function syncFromCaseAiAnalysis($user, array &$update): void
    {
        // Найти заявки клиента
        $clientIds = \DB::table('clients')
            ->where('public_user_id', $user->id)
            ->pluck('id');

        if ($clientIds->isEmpty()) return;

        $caseIds = \DB::table('cases')
            ->whereIn('client_id', $clientIds)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($caseIds->isEmpty()) return;

        // AI-проанализированные документы из чеклиста
        $items = CaseChecklist::whereIn('case_id', $caseIds)
            ->whereNotNull('ai_confidence')
            ->where('ai_confidence', '>=', 30)
            ->orderByDesc('ai_confidence')
            ->get();

        foreach ($items as $item) {
            $ai = $item->ai_analysis ?? [];
            $extracted = $ai['extracted_data'] ?? [];
            if (empty($extracted)) continue;

            $name = mb_strtolower($item->name ?? '');

            // Определить тип документа по имени в чеклисте
            $isPassport = str_contains($name, 'аспорт') && !str_contains($name, 'внутрен');
            $isId = str_contains($name, 'внутрен') || str_contains($name, 'удостоверен')
                || str_contains($name, 'id') || str_contains($name, 'id_card');

            if ($isId) {
                // Внутренний паспорт / ID-карта -> удостоверение личности клиента
                $docNumber = $extracted['passport_number'] ?? $extracted['document_number'] ?? null;
                if (!($user->id_doc_number ?? $update['id_doc_number'] ?? null) && $docNumber) {
                    $update['id_doc_number'] = $docNumber;
                }
                if (!($user->id_doc_type ?? $update['id_doc_type'] ?? null)) {
                    $update['id_doc_type'] = str_contains($name, 'внутрен') ? 'internal_passport' : 'id_card';
                }
                $expiryDate = $extracted['expiry_date'] ?? $extracted['date_of_expiry'] ?? null;
                if (!($user->id_doc_expires_at ?? $update['id_doc_expires_at'] ?? null) && $expiryDate) {
                    $update['id_doc_expires_at'] = $expiryDate;
                }
                $issueDate = $extracted['issue_date'] ?? $extracted['date_of_issue'] ?? null;
                if (!($user->id_doc_issue_date ?? $update['id_doc_issue_date'] ?? null) && $issueDate) {
                    $update['id_doc_issue_date'] = $issueDate;
                }
                $issuedBy = $extracted['issuing_authority'] ?? $extracted['issued_by'] ?? null;
                if (!($user->id_doc_issued_by ?? $update['id_doc_issued_by'] ?? null) && $issuedBy) {
                    $update['id_doc_issued_by'] = $issuedBy;
                }
                if ($user->id_doc_ocr_status !== 'completed') {
                    $update['id_doc_ocr_status'] = 'completed';
                }

                // Кириллические имена из внутреннего документа
                $surname = $extracted['surname'] ?? $extracted['last_name'] ?? null;
                $givenNames = $extracted['given_names'] ?? $extracted['first_name'] ?? null;
                if (!($user->first_name_cyr ?? $update['first_name_cyr'] ?? null) && $givenNames) {
                    $firstName = explode(' ', $givenNames)[0] ?? $givenNames;
                    $update['first_name_cyr'] = $firstName;
                }
                if (!($user->last_name_cyr ?? $update['last_name_cyr'] ?? null) && $surname) {
                    $update['last_name_cyr'] = $surname;
                }

                // ПИНФЛ из ID
                $pnfl = $extracted['pnfl'] ?? $extracted['pinfl'] ?? $extracted['personal_number'] ?? null;
                if (!($user->pnfl ?? $update['pnfl'] ?? null) && $pnfl && preg_match('/^\d{14}$/', $pnfl)) {
                    $update['pnfl'] = $pnfl;
                }
            }

            if ($isPassport) {
                // Загранпаспорт -> паспортные данные клиента
                $passNum = $extracted['passport_number'] ?? null;
                if (!($user->passport_number ?? $update['passport_number'] ?? null) && $passNum) {
                    $update['passport_number'] = $passNum;
                }
                $expiryDate = $extracted['expiry_date'] ?? $extracted['date_of_expiry'] ?? null;
                if (!($user->passport_expires_at ?? $update['passport_expires_at'] ?? null) && $expiryDate) {
                    $update['passport_expires_at'] = $expiryDate;
                }
                $issueDate = $extracted['issue_date'] ?? $extracted['date_of_issue'] ?? null;
                if (!($user->passport_issue_date ?? $update['passport_issue_date'] ?? null) && $issueDate) {
                    $update['passport_issue_date'] = $issueDate;
                }
                $issuedBy = $extracted['issuing_authority'] ?? $extracted['issued_by'] ?? null;
                if (!($user->passport_issued_by ?? $update['passport_issued_by'] ?? null) && $issuedBy) {
                    $update['passport_issued_by'] = $issuedBy;
                }
                if ($user->passport_ocr_status !== 'completed') {
                    $update['passport_ocr_status'] = 'completed';
                }

                // Латинские имена из загранпаспорта
                $surname = $extracted['surname'] ?? $extracted['last_name'] ?? null;
                $givenNames = $extracted['given_names'] ?? $extracted['first_name'] ?? null;
                if (!($user->first_name_lat ?? $update['first_name_lat'] ?? null) && $givenNames) {
                    $firstName = explode(' ', $givenNames)[0] ?? $givenNames;
                    $update['first_name_lat'] = $firstName;
                }
                if (!($user->last_name_lat ?? $update['last_name_lat'] ?? null) && $surname) {
                    $update['last_name_lat'] = $surname;
                }
            }

            // Общие поля (из любого документа)
            $dob = $extracted['date_of_birth'] ?? null;
            if (!($user->dob ?? $update['dob'] ?? null) && $dob) {
                $update['dob'] = $dob;
            }
            $gender = $extracted['sex'] ?? $extracted['gender'] ?? null;
            if (!($user->gender ?? $update['gender'] ?? null) && $gender) {
                $update['gender'] = strtoupper(substr($gender, 0, 1));
            }
        }
    }

    /**
     * Определить тип документа по OCR данным (эвристика).
     */
    private function detectDocumentType(PassportData $data): ?string
    {
        // Есть MRZ -> загранпаспорт
        if ($data->mrzLine1 || $data->mrzLine2) {
            return 'foreign_passport';
        }

        // Номер формата XX0000000 -> загранпаспорт (узбекский)
        if ($data->passportNumber && preg_match('/^[A-Z]{2}\d{7}$/', $data->passportNumber)) {
            return 'foreign_passport';
        }

        // Текст на кириллице без MRZ -> внутренний документ
        $script = $data->scriptType ?? TransliterationService::detectScript(
            ($data->firstName ?? '') . ' ' . ($data->lastName ?? '')
        );

        if ($script === 'cyrillic') {
            return 'id_card';
        }

        // ПИНФЛ без MRZ -> скорее ID-карта
        if ($data->pnfl && !$data->mrzLine1) {
            return 'id_card';
        }

        // По умолчанию — загранпаспорт (основной use-case)
        if ($data->passportNumber) {
            return 'foreign_passport';
        }

        return null;
    }

    /**
     * Восстановить extracted-массив из кэшированной записи PublicUserDocument.
     */
    private function buildExtractedFromDocRecord(PublicUserDocument $doc): array
    {
        return [
            'document_type'       => $doc->doc_type,
            'name'                => trim(($doc->first_name ?? '') . ' ' . ($doc->last_name ?? '')),
            'first_name'          => $doc->first_name,
            'last_name'           => $doc->last_name,
            'middle_name'         => $doc->middle_name,
            'first_name_latin'    => $doc->ocr_raw_data['firstNameLatin'] ?? null,
            'last_name_latin'     => $doc->ocr_raw_data['lastNameLatin'] ?? null,
            'first_name_cyrillic' => $doc->ocr_raw_data['firstNameCyrillic'] ?? null,
            'last_name_cyrillic'  => $doc->ocr_raw_data['lastNameCyrillic'] ?? null,
            'dob'                 => $doc->dob,
            'gender'              => $doc->gender,
            'citizenship'         => $this->iso3ToIso2($doc->nationality),
            'passport_number'     => $doc->doc_number,
            'passport_expires_at' => $doc->expires_at,
            'passport_issue_date' => $doc->issue_date,
            'place_of_birth'      => $doc->place_of_birth,
            'issuing_authority'   => $doc->issued_by,
            'pnfl'                => $doc->pnfl,
            'confidence'          => $doc->ocr_confidence,
            'provider'            => $doc->ocr_provider,
            'script_type'         => $doc->script_type,
        ];
    }

    /**
     * Сохранить запись документа в историю.
     */
    private function saveDocumentRecord($user, PassportData $data, string $docType, string $filePath): PublicUserDocument
    {
        // Деактивировать предыдущий документ того же типа
        PublicUserDocument::where('public_user_id', $user->id)
            ->where('doc_type', $docType)
            ->where('is_current', true)
            ->each(fn ($doc) => $doc->markReplaced('new_document'));

        // Сбросить dismissed mismatches для этого типа (новый документ = новые данные)
        $dismissed = $user->dismissed_mismatches ?? [];
        unset($dismissed[$docType]);
        $user->update(['dismissed_mismatches' => $dismissed ?: null]);

        return PublicUserDocument::create([
            'public_user_id' => $user->id,
            'doc_type'       => $docType,
            'doc_number'     => $data->passportNumber,
            'expires_at'     => $data->dateOfExpiry,
            'issue_date'     => $data->dateOfIssue,
            'issued_by'      => $data->issuingAuthority,
            'place_of_birth' => $data->placeOfBirth,
            'country'        => $this->iso3ToIso2($data->nationality),
            'first_name'     => $data->firstName,
            'last_name'      => $data->lastName,
            'middle_name'    => $data->middleName,
            'script_type'    => $data->scriptType ?? TransliterationService::detectScript(
                ($data->firstName ?? '') . ' ' . ($data->lastName ?? '')
            ),
            'gender'         => $data->gender,
            'nationality'    => $data->nationality,
            'dob'            => $data->dateOfBirth,
            'pnfl'           => $data->pnfl,
            'mrz_line1'      => $data->mrzLine1,
            'mrz_line2'      => $data->mrzLine2,
            'ocr_provider'   => $data->provider,
            'ocr_confidence' => $data->confidence,
            'ocr_raw_data'   => $data->toArray(),
            'file_path'      => $filePath,
            'is_current'     => true,
        ]);
    }

    /**
     * Обновить поля профиля пользователя из OCR-данных документа.
     */
    private function updateUserFromDocument($user, PassportData $data, string $docType, string $filePath): array
    {
        $extracted = [
            'document_type'       => $docType,
            'name'                => trim(($data->firstName ?? '') . ' ' . ($data->lastName ?? '')),
            'first_name'          => $data->firstName,
            'last_name'           => $data->lastName,
            'middle_name'         => $data->middleName,
            'first_name_latin'    => $data->firstNameLatin,
            'last_name_latin'     => $data->lastNameLatin,
            'first_name_cyrillic' => $data->firstNameCyrillic,
            'last_name_cyrillic'  => $data->lastNameCyrillic,
            'dob'                 => $data->dateOfBirth,
            'gender'              => $data->gender,
            'citizenship'         => $this->iso3ToIso2($data->nationality),
            'passport_number'     => $data->passportNumber,
            'passport_expires_at' => $data->dateOfExpiry,
            'passport_issue_date' => $data->dateOfIssue,
            'place_of_birth'      => $data->placeOfBirth,
            'issuing_authority'   => $data->issuingAuthority,
            'pnfl'                => $data->pnfl,
            'confidence'          => $data->confidence,
            'provider'            => $data->provider,
            'script_type'         => $data->scriptType,
        ];

        $profileUpdate = [];

        if ($docType === 'foreign_passport') {
            // Загранпаспорт — основной источник для визовых данных
            $profileUpdate['passport_ocr_status'] = 'completed';
            $profileUpdate['passport_ocr_data']   = array_merge(['file_path' => $filePath], $extracted);
            $profileUpdate['passport_file_path']   = $filePath;
            // Обратная совместимость
            $profileUpdate['ocr_status']   = 'completed';
            $profileUpdate['ocr_raw_data'] = array_merge(['file_path' => $filePath], $extracted);

            if ($data->passportNumber) $profileUpdate['passport_number']     = $data->passportNumber;
            if ($data->dateOfExpiry)   $profileUpdate['passport_expires_at'] = $data->dateOfExpiry;
            if ($data->dateOfIssue)    $profileUpdate['passport_issue_date'] = $data->dateOfIssue;
            if ($data->issuingAuthority) $profileUpdate['passport_issued_by'] = $data->issuingAuthority;
            if ($data->nationality)    $profileUpdate['passport_country']    = $this->iso3ToIso2($data->nationality);

            // Латинские имена из загранпаспорта
            $scriptType = $data->scriptType ?? TransliterationService::detectScript(
                ($data->firstName ?? '') . ' ' . ($data->lastName ?? '')
            );
            if ($scriptType === 'latin' || $data->firstNameLatin) {
                if ($data->firstNameLatin ?? $data->firstName) $profileUpdate['first_name_lat'] = $data->firstNameLatin ?? $data->firstName;
                if ($data->lastNameLatin ?? $data->lastName)   $profileUpdate['last_name_lat']  = $data->lastNameLatin ?? $data->lastName;
                if ($data->middleName)  $profileUpdate['middle_name_lat'] = $data->middleName;
            }
        } else {
            // ID-карта / внутренний паспорт
            $profileUpdate['id_doc_type']       = $docType;
            $profileUpdate['id_doc_ocr_status'] = 'completed';
            $profileUpdate['id_doc_ocr_data']   = array_merge(['file_path' => $filePath], $extracted);
            $profileUpdate['id_doc_file_path']   = $filePath;

            if ($data->passportNumber)   $profileUpdate['id_doc_number']     = $data->passportNumber;
            if ($data->dateOfExpiry)     $profileUpdate['id_doc_expires_at'] = $data->dateOfExpiry;
            if ($data->dateOfIssue)      $profileUpdate['id_doc_issue_date'] = $data->dateOfIssue;
            if ($data->issuingAuthority) $profileUpdate['id_doc_issued_by']  = $data->issuingAuthority;

            // Кириллические имена из ID
            $scriptType = $data->scriptType ?? TransliterationService::detectScript(
                ($data->firstName ?? '') . ' ' . ($data->lastName ?? '')
            );
            if ($scriptType === 'cyrillic' || $data->firstNameCyrillic) {
                if ($data->firstNameCyrillic ?? $data->firstName) $profileUpdate['first_name_cyr'] = $data->firstNameCyrillic ?? $data->firstName;
                if ($data->lastNameCyrillic ?? $data->lastName)   $profileUpdate['last_name_cyr']  = $data->lastNameCyrillic ?? $data->lastName;
                if ($data->middleName)  $profileUpdate['middle_name_cyr'] = $data->middleName;
            }
        }

        // Общие поля профиля — заполняем если пусто
        if (!$user->name && $data->firstName) {
            $profileUpdate['name'] = trim(($data->firstName ?? '') . ' ' . ($data->lastName ?? ''));
        }
        if (!$user->dob && $data->dateOfBirth)          $profileUpdate['dob']         = $data->dateOfBirth;
        if (!$user->gender && $data->gender)             $profileUpdate['gender']      = $data->gender;
        if (!$user->citizenship && $data->nationality)   $profileUpdate['citizenship'] = $this->iso3ToIso2($data->nationality);
        if (!$user->place_of_birth && $data->placeOfBirth) $profileUpdate['place_of_birth'] = $data->placeOfBirth;
        if (!$user->pnfl && $data->pnfl)                 $profileUpdate['pnfl']        = $data->pnfl;

        $user->update($profileUpdate);

        return $extracted;
    }

    /**
     * Сверка данных профиля с OCR-результатом.
     * Использует TransliterationService для умного сравнения имён.
     */
    private function detectMismatches($user, array $extracted, string $docType = 'foreign_passport'): array
    {
        $mismatches = [];

        // Для имён используем умную сверку
        $ocrName = $extracted['name'] ?? null;
        $currentName = $user->name;
        if ($ocrName && $currentName) {
            $nameComparison = TransliterationService::compareNames($ocrName, $currentName);
            if (!$nameComparison['match']) {
                $mismatches[] = [
                    'field'      => 'name',
                    'current'    => $currentName,
                    'ocr'        => $ocrName,
                    'level'      => $nameComparison['level'],
                    'similarity' => $nameComparison['similarity'],
                ];
            } elseif ($nameComparison['level'] === 'translit' || $nameComparison['level'] === 'close') {
                $mismatches[] = [
                    'field'      => 'name',
                    'current'    => $currentName,
                    'ocr'        => $ocrName,
                    'level'      => $nameComparison['level'],
                    'similarity' => $nameComparison['similarity'],
                    'info'       => 'translit_difference',
                ];
            }
        }

        // Остальные поля — точное сравнение
        $checks = [
            ['field' => 'dob',                 'ocr' => $extracted['dob'] ?? null,                 'current' => $user->dob],
            ['field' => 'gender',              'ocr' => $extracted['gender'] ?? null,              'current' => $user->gender],
            ['field' => 'citizenship',         'ocr' => $extracted['citizenship'] ?? null,         'current' => $user->citizenship],
        ];

        if ($docType === 'foreign_passport') {
            $checks[] = ['field' => 'passport_number',     'ocr' => $extracted['passport_number'] ?? null,     'current' => $user->passport_number];
            $checks[] = ['field' => 'passport_expires_at', 'ocr' => $extracted['passport_expires_at'] ?? null, 'current' => $user->passport_expires_at];
        } else {
            $checks[] = ['field' => 'id_doc_number', 'ocr' => $extracted['passport_number'] ?? null, 'current' => $user->id_doc_number];
        }

        foreach ($checks as $check) {
            $ocr     = $check['ocr'] ? strtoupper(trim($check['ocr'])) : null;
            $current = $check['current'] ? strtoupper(trim($check['current'])) : null;

            if (!$ocr) continue;
            if (!$current) continue;

            if ($ocr !== $current) {
                $mismatches[] = [
                    'field'   => $check['field'],
                    'current' => $check['current'],
                    'ocr'     => $check['ocr'],
                    'level'   => 'different',
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Кросс-валидация между ID и загранпаспортом.
     */
    private function crossDocumentValidation($user): ?array
    {
        $passport = $user->currentForeignPassport();
        $idDoc    = $user->currentIdDocument();

        if (!$passport || !$idDoc) return null;

        $nameComparison = TransliterationService::compareFullNames(
            $passport->first_name, $passport->last_name,
            $idDoc->first_name, $idDoc->last_name,
            $passport->middle_name, $idDoc->middle_name,
        );

        $dobMatch = ($passport->dob && $idDoc->dob)
            ? strtoupper(trim($passport->dob)) === strtoupper(trim($idDoc->dob))
            : null;

        $genderMatch = ($passport->gender && $idDoc->gender)
            ? $passport->gender === $idDoc->gender
            : null;

        return [
            'name'   => $nameComparison,
            'dob'    => $dobMatch === null ? null : ['match' => $dobMatch, 'passport' => $passport->dob, 'id' => $idDoc->dob],
            'gender' => $genderMatch === null ? null : ['match' => $genderMatch],
            'note'   => $nameComparison['level'] === 'translit'
                ? 'transliteration_difference'
                : ($nameComparison['match'] ? 'documents_consistent' : 'documents_inconsistent'),
        ];
    }

    /**
     * Проверить наличие загруженного паспорта в заявках.
     */
    private function checkPassportInCase($user): bool
    {
        return $this->checkDocInCase($user, ['%аспорт%', '%passport%']);
    }

    private function checkIdInCase($user): bool
    {
        return $this->checkDocInCase($user, ['%удостоверен%', '%ID%', '%id.card%', '%id_card%']);
    }

    private function checkDocInCase($user, array $namePatterns): bool
    {
        return CaseChecklist::whereHas('document')
            ->whereIn('case_id', function ($q) use ($user) {
                $q->select('id')
                    ->from('cases')
                    ->whereIn('client_id', function ($q2) use ($user) {
                        $q2->select('id')
                            ->from('clients')
                            ->where('public_user_id', $user->id);
                    });
            })
            ->where(function ($q) use ($namePatterns) {
                foreach ($namePatterns as $pattern) {
                    $q->orWhere('name', 'like', $pattern);
                }
            })
            ->whereIn('status', ['uploaded', 'approved'])
            ->exists();
    }

    /**
     * Форматировать документ для фронтенда.
     */
    private function formatDocumentForFrontend(PublicUserDocument $doc): array
    {
        return [
            'id'              => $doc->id,
            'document_type'   => $doc->doc_type,
            'doc_number'      => $doc->doc_number,
            'expires_at'      => $doc->expires_at,
            'issue_date'      => $doc->issue_date,
            'issued_by'       => $doc->issued_by,
            'first_name'      => $doc->first_name,
            'last_name'       => $doc->last_name,
            'middle_name'     => $doc->middle_name,
            'script_type'     => $doc->script_type,
            'gender'          => $doc->gender,
            'nationality'     => $doc->nationality,
            'dob'             => $doc->dob,
            'pnfl'            => $doc->pnfl,
            'place_of_birth'  => $doc->place_of_birth,
            'confidence'      => $doc->ocr_confidence,
            'provider'        => $doc->ocr_provider,
            'is_current'      => $doc->is_current,
            'created_at'      => $doc->created_at?->toDateTimeString(),
            // Обратная совместимость
            'name'            => trim(($doc->first_name ?? '') . ' ' . ($doc->last_name ?? '')),
            'citizenship'     => $doc->country,
            'passport_number' => $doc->doc_number,
            'passport_expires_at' => $doc->expires_at,
            'passport_issue_date' => $doc->issue_date,
        ];
    }

    private function iso3ToIso2(?string $iso3): ?string
    {
        if (!$iso3) return null;
        $map = [
            'UZB' => 'UZ', 'RUS' => 'RU', 'KAZ' => 'KZ', 'TJK' => 'TJ',
            'KGZ' => 'KG', 'TKM' => 'TM', 'UKR' => 'UA', 'GEO' => 'GE',
            'AZE' => 'AZ', 'ARM' => 'AM', 'BLR' => 'BY', 'MDA' => 'MD',
        ];
        return $map[strtoupper(trim($iso3))] ?? null;
    }
}
