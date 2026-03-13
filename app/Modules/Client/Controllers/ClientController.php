<?php

namespace App\Modules\Client\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Client\Requests\StoreClientRequest;
use App\Modules\Client\Requests\UpdateClientRequest;
use App\Modules\Client\Resources\ClientResource;
use App\Modules\Client\Services\ClientService;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Services\OcrService;
use App\Modules\PublicPortal\Models\PublicScoreCache;
use App\Modules\PublicPortal\Models\PublicUser;
use App\Modules\Scoring\Models\ClientProfile;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientController extends Controller
{
    public function __construct(
        protected ClientService $service,
        protected OcrService $ocrService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->filled('q')) {
            $clients = $this->service->search($request->q);

            return ApiResponse::success(ClientResource::collection($clients));
        }

        $clients = $this->service->paginate(20);

        return ApiResponse::paginated($clients, 'Success', ClientResource::class);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();

        $client = $this->service->create($data);

        return ApiResponse::created(new ClientResource($client));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('view', $client);

        return ApiResponse::success(
            (new ClientResource($client->load(['cases' => fn ($q) => $q->with('assignee:id,name')->orderBy('created_at', 'desc')])))
                ->withPii()
        );
    }

    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('update', $client);

        $client = $this->service->update($id, $request->validated());

        return ApiResponse::success(new ClientResource($client));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('delete', $client);

        $this->service->delete($id);

        return ApiResponse::success(null, 'Client deleted.');
    }

    /**
     * GET /clients/{id}/visabor-scoring
     * Скоринг клиента из личного кабинета VisaBor.
     */
    public function visaborScoring(Request $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('view', $client);

        if (!$client->public_user_id) {
            return ApiResponse::success([]);
        }

        $scores = PublicScoreCache::where('public_user_id', $client->public_user_id)
            ->orderByDesc('score')
            ->get(['country_code', 'score', 'breakdown', 'calculated_at']);

        return ApiResponse::success($scores);
    }

    /**
     * POST /clients/{id}/apply-ai-data
     * Автозаполнение данных клиента из AI-анализа документов в кейсах.
     */
    public function applyAiData(Request $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('update', $client);

        // Собираем все AI-анализы из чеклистов всех кейсов клиента
        $caseIds = $client->cases()->pluck('id');
        $items = CaseChecklist::whereIn('case_id', $caseIds)
            ->whereNotNull('ai_analysis')
            ->where('ai_confidence', '>', 0)
            ->orderByDesc('ai_confidence')
            ->get();

        $clientUpdates = [];
        $profileUpdates = [];
        $applied = [];

        foreach ($items as $item) {
            $data = $item->ai_analysis['extracted_data'] ?? [];
            if (empty($data)) continue;

            $name = $item->name;
            $isApplicant = empty($item->family_member_id); // только документы заявителя

            // Загранпаспорт — основные данные клиента (только заявитель)
            if ($isApplicant && (str_contains($name, 'Загранпаспорт') || str_contains($name, 'загранпаспорт'))) {
                if (!empty($data['passport_number']) && empty($clientUpdates['passport_number'])) {
                    $clientUpdates['passport_number'] = $data['passport_number'];
                }
                if (!empty($data['expiry_date']) && empty($clientUpdates['passport_expires_at'])) {
                    $clientUpdates['passport_expires_at'] = $data['expiry_date'];
                }
                if (!empty($data['date_of_birth']) && empty($clientUpdates['date_of_birth'])) {
                    $clientUpdates['date_of_birth'] = $data['date_of_birth'];
                }
                if (!empty($data['nationality']) && empty($clientUpdates['nationality'])) {
                    $clientUpdates['nationality'] = $this->nationalityToAlpha3($data['nationality']);
                }
                $applied[] = $name;
            }

            // Внутренний паспорт — дата рождения, место рождения (только заявитель)
            if ($isApplicant && (str_contains($name, 'Внутренний паспорт') || str_contains($name, 'внутренний паспорт'))) {
                if (!empty($data['date_of_birth']) && empty($clientUpdates['date_of_birth'])) {
                    $clientUpdates['date_of_birth'] = $data['date_of_birth'];
                }
                $applied[] = $name;
            }

            // Свидетельство о браке → married
            if (str_contains($name, 'браке') || str_contains($name, 'Свидетельство о браке')) {
                if (!empty($data['marriage_date'])) {
                    $profileUpdates['marital_status'] = 'married';
                }
                $applied[] = $name;
            }

            // Справка об остатке на счёте
            if (str_contains($name, 'остатке') || str_contains($name, 'банк') || str_contains($name, 'счёте')) {
                if (!empty($data['balance'])) {
                    $profileUpdates['bank_balance'] = (float) $data['balance'];
                }
                $applied[] = $name;
            }

            // Выписка о недвижимости
            if ($isApplicant && (str_contains($name, 'недвижимост') || str_contains($name, 'Выписка о недвижимост'))) {
                if (!empty($data['owner_name'])) {
                    $profileUpdates['has_real_estate'] = true;
                }
                $applied[] = $name;
            }

            // Техпаспорт автомобиля
            if ($isApplicant && (str_contains($name, 'техпаспорт') || str_contains($name, 'Техпаспорт') || str_contains($name, 'автомобил'))) {
                $profileUpdates['has_car'] = true;
                $applied[] = $name;
            }

            // Трудовая книжка
            if ($isApplicant && (str_contains($name, 'Трудовая') || str_contains($name, 'трудовая'))) {
                if (!empty($data['employer_name']) && empty($profileUpdates['employer_name'])) {
                    $profileUpdates['employer_name'] = $data['employer_name'];
                }
                if (!empty($data['position']) && empty($profileUpdates['position'])) {
                    $profileUpdates['position'] = $data['position'];
                }
                $applied[] = $name;
            }

            // Авиабилеты
            if ($isApplicant && (str_contains($name, 'Авиабилет') || str_contains($name, 'авиабилет'))) {
                $profileUpdates['has_return_ticket'] = true;
                $applied[] = $name;
            }

            // Метрика ребёнка
            if (str_contains($name, 'Метрика') || str_contains($name, 'метрика')) {
                $existingChildren = $profileUpdates['children_count'] ?? 0;
                $profileUpdates['children_count'] = $existingChildren + 1;
                $profileUpdates['children_staying_home'] = true;
                $applied[] = $name;
            }
        }

        // Синхронизация из публичного портала VisaBor (если клиент зарегистрирован)
        if ($client->public_user_id) {
            $pu = PublicUser::find($client->public_user_id);
            if ($pu) {
                // Базовые данные клиента из портала
                if ($pu->dob && empty($clientUpdates['date_of_birth'])) {
                    $clientUpdates['date_of_birth'] = $pu->dob;
                }
                if ($pu->citizenship && empty($clientUpdates['nationality'])) {
                    // Портал хранит ISO-2 (UZ), CRM — ISO-3 (UZB)
                    $clientUpdates['nationality'] = $this->iso2ToIso3($pu->citizenship);
                }
                if ($pu->passport_number && empty($clientUpdates['passport_number'])) {
                    $clientUpdates['passport_number'] = $pu->passport_number;
                }
                if ($pu->passport_expires_at && empty($clientUpdates['passport_expires_at'])) {
                    $clientUpdates['passport_expires_at'] = $pu->passport_expires_at;
                }

                // Профиль из портала — маппинг public_users полей → client_profiles
                $employmentMap = [
                    'employed' => 'private', 'government' => 'government', 'business_owner' => 'business_owner',
                    'self_employed' => 'self_employed', 'student' => 'student', 'retired' => 'retired',
                    'unemployed' => 'unemployed',
                ];
                if ($pu->employment_type) {
                    $profileUpdates['employment_type'] = $profileUpdates['employment_type']
                        ?? ($employmentMap[$pu->employment_type] ?? $pu->employment_type);
                }
                if ($pu->monthly_income_usd) {
                    $profileUpdates['monthly_income'] = $profileUpdates['monthly_income'] ?? $pu->monthly_income_usd;
                }
                if ($pu->marital_status) {
                    $profileUpdates['marital_status'] = $profileUpdates['marital_status'] ?? $pu->marital_status;
                }
                if ($pu->has_children && $pu->children_count) {
                    $profileUpdates['children_count'] = $profileUpdates['children_count'] ?? $pu->children_count;
                    $profileUpdates['children_staying_home'] = $profileUpdates['children_staying_home'] ?? true;
                }
                if ($pu->has_property) {
                    $profileUpdates['has_real_estate'] = $profileUpdates['has_real_estate'] ?? true;
                }
                if ($pu->has_car) {
                    $profileUpdates['has_car'] = $profileUpdates['has_car'] ?? true;
                }
                if ($pu->has_schengen_visa) {
                    $profileUpdates['has_schengen_visa'] = $profileUpdates['has_schengen_visa'] ?? true;
                }
                if ($pu->has_us_visa) {
                    $profileUpdates['has_us_visa'] = $profileUpdates['has_us_visa'] ?? true;
                }
                if ($pu->had_visa_refusal && $pu->refusals_count) {
                    $profileUpdates['previous_refusals'] = $profileUpdates['previous_refusals'] ?? $pu->refusals_count;
                }
                if ($pu->had_overstay) {
                    $profileUpdates['has_overstay'] = $profileUpdates['has_overstay'] ?? true;
                }
                if ($pu->education_level) {
                    $profileUpdates['education_level'] = $profileUpdates['education_level'] ?? $pu->education_level;
                }
                if ($pu->employed_years) {
                    $yearsMap = ['less_1' => 0.5, '1_2' => 1.5, '2_5' => 3.5, '5_10' => 7, 'more_10' => 12];
                    $profileUpdates['years_at_current_job'] = $profileUpdates['years_at_current_job']
                        ?? ($yearsMap[$pu->employed_years] ?? 0);
                }

                $applied[] = 'VisaBor (профиль клиента)';
            }
        }

        // Применяем обновления Client
        if (!empty($clientUpdates)) {
            // Не перезаписываем уже заполненные поля
            $toUpdate = [];
            foreach ($clientUpdates as $field => $value) {
                if (empty($client->$field)) {
                    $toUpdate[$field] = $value;
                }
            }
            if (!empty($toUpdate)) {
                $client->update($toUpdate);
            }
        }

        // Применяем обновления ClientProfile
        if (!empty($profileUpdates)) {
            $profile = ClientProfile::firstOrCreate(
                ['client_id' => $client->id],
                ['client_id' => $client->id]
            );
            // Не перезаписываем уже заполненные поля
            $toUpdate = [];
            foreach ($profileUpdates as $field => $value) {
                if (empty($profile->$field)) {
                    $toUpdate[$field] = $value;
                }
            }
            if (!empty($toUpdate)) {
                $profile->update($toUpdate);
            }
        }

        $client->refresh();
        $profile = ClientProfile::where('client_id', $client->id)->first();

        // VisaBor scoring из кабинета клиента
        $visaborScoring = null;
        if ($client->public_user_id) {
            $visaborScoring = PublicScoreCache::where('public_user_id', $client->public_user_id)
                ->orderByDesc('score')
                ->get(['country_code', 'score', 'breakdown', 'calculated_at']);
        }

        return ApiResponse::success([
            'client'  => (new ClientResource($client))->withPii(),
            'profile' => $profile,
            'applied_from' => array_unique($applied),
            'visabor_scoring' => $visaborScoring,
        ], 'Данные клиента обновлены из AI-анализа документов.');
    }

    private function iso2ToIso3(string $iso2): string
    {
        $map = [
            'UZ' => 'UZB', 'RU' => 'RUS', 'KZ' => 'KAZ', 'TJ' => 'TJK',
            'KG' => 'KGZ', 'TM' => 'TKM', 'UA' => 'UKR', 'GE' => 'GEO',
            'AZ' => 'AZE', 'AM' => 'ARM', 'BY' => 'BLR', 'MD' => 'MDA',
            'TR' => 'TUR', 'CN' => 'CHN', 'IN' => 'IND', 'AF' => 'AFG',
            'PK' => 'PAK', 'BD' => 'BGD', 'IR' => 'IRN', 'IQ' => 'IRQ',
        ];
        $code = strtoupper(trim($iso2));
        return $map[$code] ?? $code;
    }

    private function nationalityToAlpha3(string $nationality): string
    {
        $map = [
            'UZBEKISTAN'  => 'UZB',
            'RUSSIAN'     => 'RUS',
            'RUSSIA'      => 'RUS',
            'KAZAKHSTAN'  => 'KAZ',
            'TAJIKISTAN'  => 'TJK',
            'KYRGYZSTAN'  => 'KGZ',
            'TURKMENISTAN' => 'TKM',
            'UKRAINE'     => 'UKR',
            'GEORGIA'     => 'GEO',
            'AZERBAIJAN'  => 'AZE',
        ];
        return $map[strtoupper(trim($nationality))] ?? strtoupper(substr($nationality, 0, 3));
    }

    /**
     * POST /clients/parse-passport
     * Загрузка фото паспорта и OCR-распознавание данных.
     */
    public function parsePassport(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);

        $agencyId = $request->user()->agency_id;
        $file     = $request->file('file');
        $path     = $file->store("agencies/{$agencyId}/passports", 'documents');
        $fullPath = Storage::disk('documents')->path($path);

        try {
            $passportData = $this->ocrService->extractPassport($fullPath);

            return ApiResponse::success([
                'file_path'           => $path,
                'ocr_status'          => 'completed',
                'name'                => trim(($passportData->firstName ?? '') . ' ' . ($passportData->lastName ?? '')),
                'first_name'          => $passportData->firstName,
                'last_name'           => $passportData->lastName,
                'middle_name'         => $passportData->middleName,
                'date_of_birth'       => $passportData->dateOfBirth,
                'passport_number'     => $passportData->passportNumber,
                'passport_expires_at' => $passportData->dateOfExpiry,
                'nationality'         => $passportData->nationality,
                'gender'              => $passportData->gender,
                'confidence'          => $passportData->confidence,
                'provider'            => $passportData->provider,
            ]);
        } catch (\Throwable $e) {
            Log::warning('parsePassport OCR failed, returning empty fields', [
                'agency_id' => $agencyId,
                'error'     => $e->getMessage(),
            ]);

            return ApiResponse::success([
                'file_path'           => $path,
                'ocr_status'          => 'failed',
                'ocr_error'           => $e->getMessage(),
                'name'                => null,
                'date_of_birth'       => null,
                'passport_number'     => null,
                'passport_expires_at' => null,
                'nationality'         => null,
            ], 'Файл загружен, но OCR-распознавание не удалось. Заполните поля вручную.');
        }
    }
}
