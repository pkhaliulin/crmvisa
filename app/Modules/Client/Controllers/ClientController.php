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
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
            (new ClientResource($client->load(['publicUser', 'cases' => fn ($q) => $q->with('assignee:id,name')->orderBy('created_at', 'desc')])))
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
     * GET /clients/{id}/profile
     * Профиль клиента из public_users (единый источник данных).
     */
    public function profile(Request $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('view', $client);

        $pu = $this->ensurePublicUser($client);

        return ApiResponse::success($this->formatProfile($pu));
    }

    /**
     * PATCH /clients/{id}/profile
     * Обновить профиль клиента (агентство редактирует public_users).
     */
    public function updateProfile(Request $request, string $id): JsonResponse
    {
        $client = $this->service->findOrFail($id);
        $this->authorize('update', $client);

        $data = $request->validate([
            'name'                => 'sometimes|string|max:255',
            'dob'                 => 'sometimes|nullable|date|before:today',
            'citizenship'         => 'sometimes|nullable|string|size:2',
            'gender'              => ['sometimes', 'nullable', Rule::in(['M', 'F'])],
            'passport_number'     => 'sometimes|nullable|string|max:20',
            'passport_expires_at' => 'sometimes|nullable|date',
            'employment_type'     => ['sometimes', 'nullable', Rule::in([
                'employed', 'government', 'business_owner', 'self_employed', 'student', 'retired', 'unemployed',
            ])],
            'monthly_income_usd'  => 'sometimes|nullable|integer|min:0',
            'marital_status'      => ['sometimes', 'nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'has_children'        => 'sometimes|boolean',
            'children_count'      => 'sometimes|integer|min:0|max:20',
            'has_property'        => 'sometimes|boolean',
            'has_car'             => 'sometimes|boolean',
            'has_schengen_visa'   => 'sometimes|boolean',
            'has_us_visa'         => 'sometimes|boolean',
            'had_visa_refusal'    => 'sometimes|boolean',
            'had_overstay'        => 'sometimes|boolean',
            'had_deportation'     => 'sometimes|boolean',
            'visas_obtained_count' => 'sometimes|integer|min:0|max:50',
            'refusals_count'      => 'sometimes|integer|min:0|max:20',
            'employed_years'      => 'sometimes|nullable|integer|min:0|max:50',
            'education_level'     => ['sometimes', 'nullable', Rule::in(['none', 'secondary', 'bachelor', 'master', 'phd'])],
        ]);

        $pu = $this->ensurePublicUser($client);
        $pu->update($data);

        // Синхронизируем базовые поля обратно в clients
        $clientSync = [];
        if (isset($data['name'])) $clientSync['name'] = $data['name'];
        if (isset($data['dob'])) $clientSync['date_of_birth'] = $data['dob'];
        if (isset($data['passport_number'])) $clientSync['passport_number'] = $data['passport_number'];
        if (isset($data['passport_expires_at'])) $clientSync['passport_expires_at'] = $data['passport_expires_at'];
        if (isset($data['citizenship'])) $clientSync['nationality'] = $this->iso2ToIso3($data['citizenship']);
        if (!empty($clientSync)) {
            $client->update($clientSync);
        }

        return ApiResponse::success($this->formatProfile($pu->fresh()));
    }

    /**
     * Гарантирует наличие public_user для клиента (создаёт при необходимости).
     */
    private function ensurePublicUser($client): PublicUser
    {
        if ($client->public_user_id) {
            return PublicUser::findOrFail($client->public_user_id);
        }

        // Авто-создание public_user для прямых клиентов
        $pu = PublicUser::create([
            'phone' => $client->phone ?? 'agency_' . $client->id,
            'name'  => $client->name,
            'dob'   => $client->date_of_birth,
            'passport_number'     => $client->passport_number,
            'passport_expires_at' => $client->passport_expires_at,
            'citizenship'         => $client->nationality ? $this->iso3ToIso2($client->nationality) : null,
        ]);

        $client->update(['public_user_id' => $pu->id]);

        return $pu;
    }

    /**
     * Форматирует PublicUser в профиль для агентства.
     */
    private function formatProfile(PublicUser $pu): array
    {
        return [
            'id'                   => $pu->id,
            'name'                 => $pu->name,
            'dob'                  => $pu->dob,
            'citizenship'          => $pu->citizenship,
            'gender'               => $pu->gender,
            'passport_number'      => $pu->passport_number,
            'passport_expires_at'  => $pu->passport_expires_at,
            'employment_type'      => $pu->employment_type,
            'monthly_income_usd'   => $pu->monthly_income_usd,
            'employed_years'       => $pu->employed_years,
            'marital_status'       => $pu->marital_status,
            'has_children'         => $pu->has_children,
            'children_count'       => $pu->children_count,
            'has_property'         => $pu->has_property,
            'has_car'              => $pu->has_car,
            'has_schengen_visa'    => $pu->has_schengen_visa,
            'has_us_visa'          => $pu->has_us_visa,
            'had_visa_refusal'     => $pu->had_visa_refusal,
            'had_overstay'         => $pu->had_overstay,
            'had_deportation'      => $pu->had_deportation,
            'visas_obtained_count' => $pu->visas_obtained_count,
            'refusals_count'       => $pu->refusals_count,
            'education_level'      => $pu->education_level,
            'profile_percent'      => $pu->profileCompleteness(),
        ];
    }

    private function iso3ToIso2(string $iso3): ?string
    {
        $map = [
            'UZB' => 'UZ', 'RUS' => 'RU', 'KAZ' => 'KZ', 'TJK' => 'TJ',
            'KGZ' => 'KG', 'TKM' => 'TM', 'UKR' => 'UA', 'GEO' => 'GE',
            'AZE' => 'AZ', 'ARM' => 'AM', 'BLR' => 'BY', 'MDA' => 'MD',
            'TUR' => 'TR', 'CHN' => 'CN', 'IND' => 'IN', 'AFG' => 'AF',
        ];
        $code = strtoupper(trim($iso3));
        return $map[$code] ?? null;
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
        $puUpdates = [];
        $applied = [];

        // Карта slug шаблонов для AI-маппинга (надёжнее чем str_contains по имени)
        $slugMap = [
            'passport'            => 'passport',
            'international_passport' => 'passport',
            'zagranpassport'      => 'passport',
            'internal_passport'   => 'internal_passport',
            'id_card'             => 'internal_passport',
            'marriage_certificate'=> 'marriage',
            'property_extract'    => 'property',
            'property_certificate'=> 'property',
            'vehicle_registration'=> 'vehicle',
            'car_registration'    => 'vehicle',
            'birth_certificate'   => 'birth_certificate',
            'child_birth_certificate' => 'birth_certificate',
        ];

        foreach ($items as $item) {
            $data = $item->ai_analysis['extracted_data'] ?? [];
            if (empty($data)) continue;

            $isApplicant = empty($item->family_member_id);

            // Определяем тип документа по slug шаблона (SSOT) или fallback по имени
            $templateSlug = null;
            if ($item->country_requirement_id) {
                $templateSlug = \DB::table('country_visa_requirements as cvr')
                    ->join('document_templates as dt', 'dt.id', '=', 'cvr.document_template_id')
                    ->where('cvr.id', $item->country_requirement_id)
                    ->value('dt.slug');
            }

            $docType = $slugMap[$templateSlug ?? ''] ?? null;

            // Fallback: если шаблон не найден, определяем по имени
            if (!$docType) {
                $nameLower = mb_strtolower($item->name);
                if (str_contains($nameLower, 'загранпаспорт') || str_contains($nameLower, 'passport')) {
                    $docType = 'passport';
                } elseif (str_contains($nameLower, 'внутренний паспорт') || str_contains($nameLower, 'id card')) {
                    $docType = 'internal_passport';
                } elseif (str_contains($nameLower, 'браке') || str_contains($nameLower, 'marriage')) {
                    $docType = 'marriage';
                } elseif (str_contains($nameLower, 'недвижимост') || str_contains($nameLower, 'property')) {
                    $docType = 'property';
                } elseif (str_contains($nameLower, 'техпаспорт') || str_contains($nameLower, 'автомобил') || str_contains($nameLower, 'vehicle')) {
                    $docType = 'vehicle';
                } elseif (str_contains($nameLower, 'метрика') || str_contains($nameLower, 'birth')) {
                    $docType = 'birth_certificate';
                }
            }

            if (!$docType) continue;

            // Загранпаспорт
            if ($isApplicant && $docType === 'passport') {
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
                $applied[] = $item->name;
            }

            // Внутренний паспорт
            if ($isApplicant && $docType === 'internal_passport') {
                if (!empty($data['date_of_birth']) && empty($clientUpdates['date_of_birth'])) {
                    $clientUpdates['date_of_birth'] = $data['date_of_birth'];
                }
                $applied[] = $item->name;
            }

            // Свидетельство о браке
            if ($docType === 'marriage') {
                if (!empty($data['marriage_date'])) {
                    $puUpdates['marital_status'] = 'married';
                }
                $applied[] = $item->name;
            }

            // Недвижимость
            if ($isApplicant && $docType === 'property') {
                if (!empty($data['owner_name'])) {
                    $puUpdates['has_property'] = true;
                }
                $applied[] = $item->name;
            }

            // Автомобиль
            if ($isApplicant && $docType === 'vehicle') {
                $puUpdates['has_car'] = true;
                $applied[] = $item->name;
            }

            // Метрика ребёнка
            if ($docType === 'birth_certificate') {
                $existingChildren = $puUpdates['children_count'] ?? 0;
                $puUpdates['children_count'] = $existingChildren + 1;
                $puUpdates['has_children'] = true;
                $applied[] = $item->name;
            }
        }

        // Применяем обновления Client
        if (!empty($clientUpdates)) {
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

        // Применяем обновления в public_users (единый источник профиля)
        $pu = $this->ensurePublicUser($client->fresh());

        // Синхронизируем базовые данные из client → public_user (если AI заполнил)
        if (!empty($clientUpdates['date_of_birth']) && empty($pu->dob)) {
            $puUpdates['dob'] = $clientUpdates['date_of_birth'];
        }
        if (!empty($clientUpdates['passport_number']) && empty($pu->passport_number)) {
            $puUpdates['passport_number'] = $clientUpdates['passport_number'];
        }
        if (!empty($clientUpdates['passport_expires_at']) && empty($pu->passport_expires_at)) {
            $puUpdates['passport_expires_at'] = $clientUpdates['passport_expires_at'];
        }
        if (!empty($clientUpdates['nationality']) && empty($pu->citizenship)) {
            $puUpdates['citizenship'] = $this->iso3ToIso2($clientUpdates['nationality']);
        }

        if (!empty($puUpdates)) {
            $toUpdate = [];
            foreach ($puUpdates as $field => $value) {
                if (empty($pu->$field)) {
                    $toUpdate[$field] = $value;
                }
            }
            if (!empty($toUpdate)) {
                $pu->update($toUpdate);
            }
        }

        $client->refresh();
        $pu->refresh();

        // VisaBor scoring из кабинета клиента
        $visaborScoring = PublicScoreCache::where('public_user_id', $pu->id)
            ->orderByDesc('score')
            ->get(['country_code', 'score', 'breakdown', 'calculated_at']);

        return ApiResponse::success([
            'client'  => (new ClientResource($client))->withPii(),
            'profile' => $this->formatProfile($pu),
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
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240', new \App\Support\Rules\SafeFileName],
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
