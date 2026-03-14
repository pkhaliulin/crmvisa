<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Services\OcrService;
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
            'dob'                => 'sometimes|date|before:today',
            'citizenship'        => 'sometimes|string|size:2',
            'gender'             => ['sometimes', Rule::in(['M', 'F'])],
            'passport_number'    => ['sometimes', 'nullable', 'string', 'regex:/^[A-Z]{2}[0-9]{7}$/'],
            'passport_expires_at'=> 'sometimes|date|after:today',
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
     * POST /public/me/passport
     * Загрузить фото паспорта и распознать через OCR.
     */
    public function uploadPassport(Request $request): JsonResponse
    {
        $request->validate([
            'passport' => ['required', 'image', 'max:10240', new \App\Rules\SafeFileName],
        ]);

        $user = $request->get('_public_user');

        $path = $request->file('passport')->store("passports/{$user->id}", 'documents');
        $fullPath = Storage::disk('documents')->path($path);

        $user->update([
            'ocr_status'   => 'processing',
            'ocr_raw_data' => ['file_path' => $path],
        ]);

        try {
            $ocrService = app(OcrService::class);
            $ocrService->setContext(null, $user->id);
            $passportData = $ocrService->extractPassport($fullPath);

            $extracted = [
                'name'                => trim(($passportData->firstName ?? '') . ' ' . ($passportData->lastName ?? '')),
                'first_name'          => $passportData->firstName,
                'last_name'           => $passportData->lastName,
                'middle_name'         => $passportData->middleName,
                'dob'                 => $passportData->dateOfBirth,
                'gender'              => $passportData->gender,
                'citizenship'         => $this->iso3ToIso2($passportData->nationality),
                'passport_number'     => $passportData->passportNumber,
                'passport_expires_at' => $passportData->dateOfExpiry,
                'passport_issue_date' => $passportData->dateOfIssue,
                'confidence'          => $passportData->confidence,
                'provider'            => $passportData->provider,
            ];

            $user->update([
                'ocr_status'   => 'completed',
                'ocr_raw_data' => array_merge(['file_path' => $path], $extracted),
            ]);

            // Сверка с текущими данными профиля
            $mismatches = $this->detectMismatches($user, $extracted);

            return ApiResponse::success([
                'ocr_status' => 'completed',
                'extracted'  => $extracted,
                'mismatches' => $mismatches,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Public passport OCR failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            $user->update(['ocr_status' => 'failed']);

            return ApiResponse::success([
                'ocr_status' => 'failed',
                'extracted'  => null,
                'mismatches' => [],
                'message'    => 'Не удалось распознать паспорт. Заполните данные вручную.',
            ]);
        }
    }

    /**
     * GET /public/me/passport-data
     * Получить ранее распознанные данные паспорта для сверки.
     */
    public function passportData(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        // Проверяем наличие загруженного паспорта в заявках
        $hasPassportInCase = CaseChecklist::whereHas('document')
            ->whereIn('case_id', function ($q) use ($user) {
                $q->select('id')
                    ->from('cases')
                    ->whereIn('client_id', function ($q2) use ($user) {
                        $q2->select('id')
                            ->from('clients')
                            ->where('public_user_id', $user->id);
                    });
            })
            ->where('name', 'like', '%аспорт%')
            ->whereIn('status', ['uploaded', 'approved'])
            ->exists();

        if ($user->ocr_status !== 'completed' || empty($user->ocr_raw_data)) {
            return ApiResponse::success([
                'extracted'          => null,
                'mismatches'         => [],
                'has_passport_in_case' => $hasPassportInCase,
            ]);
        }

        $extracted = $user->ocr_raw_data;
        unset($extracted['file_path']); // не отдаём путь к файлу
        unset($extracted['source']);

        $mismatches = $this->detectMismatches($user, $extracted);

        return ApiResponse::success([
            'extracted'            => $extracted,
            'mismatches'           => $mismatches,
            'has_passport_in_case' => $hasPassportInCase,
        ]);
    }

    /**
     * POST /public/me/passport-from-case
     * Распознать паспорт из уже загруженного документа в чеклисте заявки.
     */
    public function ocrFromCase(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        // Находим загруженный паспорт в чеклистах заявок пользователя
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
            ->where('name', 'like', '%аспорт%')
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

        $user->update([
            'ocr_status'   => 'processing',
            'ocr_raw_data' => ['file_path' => $doc->file_path, 'source' => 'case_checklist'],
        ]);

        try {
            $ocrService = app(OcrService::class);
            $ocrService->setContext(null, $user->id);
            $passportData = $ocrService->extractPassport($fullPath);

            $extracted = [
                'name'                => trim(($passportData->firstName ?? '') . ' ' . ($passportData->lastName ?? '')),
                'first_name'          => $passportData->firstName,
                'last_name'           => $passportData->lastName,
                'middle_name'         => $passportData->middleName,
                'dob'                 => $passportData->dateOfBirth,
                'gender'              => $passportData->gender,
                'citizenship'         => $this->iso3ToIso2($passportData->nationality),
                'passport_number'     => $passportData->passportNumber,
                'passport_expires_at' => $passportData->dateOfExpiry,
                'passport_issue_date' => $passportData->dateOfIssue,
                'confidence'          => $passportData->confidence,
                'provider'            => $passportData->provider,
            ];

            $user->update([
                'ocr_status'   => 'completed',
                'ocr_raw_data' => array_merge(['file_path' => $doc->file_path, 'source' => 'case_checklist'], $extracted),
            ]);

            $mismatches = $this->detectMismatches($user, $extracted);

            return ApiResponse::success([
                'ocr_status' => 'completed',
                'extracted'  => $extracted,
                'mismatches' => $mismatches,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Public passport OCR from case failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            $user->update(['ocr_status' => 'failed']);

            return ApiResponse::success([
                'ocr_status' => 'failed',
                'extracted'  => null,
                'mismatches' => [],
                'message'    => 'Не удалось распознать паспорт из заявки.',
            ]);
        }
    }

    /**
     * Сверка данных профиля с OCR-результатом.
     */
    private function detectMismatches($user, array $extracted): array
    {
        $mismatches = [];

        $checks = [
            ['field' => 'name',                'ocr' => $extracted['name'] ?? null,                'current' => $user->name],
            ['field' => 'dob',                 'ocr' => $extracted['dob'] ?? null,                 'current' => $user->dob],
            ['field' => 'gender',              'ocr' => $extracted['gender'] ?? null,              'current' => $user->gender],
            ['field' => 'citizenship',         'ocr' => $extracted['citizenship'] ?? null,         'current' => $user->citizenship],
            ['field' => 'passport_number',     'ocr' => $extracted['passport_number'] ?? null,     'current' => $user->passport_number],
            ['field' => 'passport_expires_at', 'ocr' => $extracted['passport_expires_at'] ?? null, 'current' => $user->passport_expires_at],
        ];

        foreach ($checks as $check) {
            $ocr     = $check['ocr'] ? strtoupper(trim($check['ocr'])) : null;
            $current = $check['current'] ? strtoupper(trim($check['current'])) : null;

            if (!$ocr) continue; // OCR не распознал — не сверяем
            if (!$current) continue; // Поле не заполнено — не расхождение, а автозаполнение

            if ($ocr !== $current) {
                $mismatches[] = [
                    'field'   => $check['field'],
                    'current' => $check['current'],
                    'ocr'     => $check['ocr'],
                ];
            }
        }

        return $mismatches;
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
