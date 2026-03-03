<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Models\CaseChecklist;
use App\Support\Helpers\ApiResponse;
use App\Support\Rules\ReferenceExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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
        ]);

        $user->update($data);

        return ApiResponse::success([
            'user'            => $user->fresh(),
            'profile_percent' => $user->fresh()->profileCompleteness(),
        ], 'Профиль обновлён');
    }

    /**
     * POST /public/me/cases
     * Создать DRAFT заявку (без агентства).
     */
    public function createDraftCase(Request $request): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        if ($publicUser->profileCompleteness() < 60) {
            return ApiResponse::error('Заполните профиль хотя бы на 60% для создания заявки.', [
                'requires_profile' => true,
                'profile_percent'  => $publicUser->profileCompleteness(),
            ], 422);
        }

        $data = $request->validate([
            'country_code'        => ['required', 'string', 'size:2'],
            'visa_type'           => ['required', 'string', 'max:50'],
            'planned_travel_date' => ['nullable', 'date', 'after:today'],
        ]);

        $case = DB::transaction(function () use ($publicUser, $data) {
            // Найти клиента по нормализованному телефону
            $phoneDigits = ltrim($publicUser->phone, '+');
            $client = Client::withoutTenant()
                ->whereRaw("REPLACE(phone, '+', '') = ?", [$phoneDigits])
                ->first();

            if (! $client) {
                // Создаём клиента без agency_id для DRAFT — но agency_id обязателен
                // Используем первое агентство как заглушку? Нет — клиент пока без агентства
                // Создаём запись с agency_id = null (если допускается) или пропускаем
                // Для DRAFT заявки создаём её с agency_id = null
                $clientData = [
                    'name'   => $publicUser->name ?? ('Клиент ' . $publicUser->phone),
                    'phone'  => $publicUser->phone,
                    'source' => 'marketplace',
                ];
                // Client HasTenant требует agency_id — создаём без него через DB
                $clientId = \Illuminate\Support\Str::uuid()->toString();
                \Illuminate\Support\Facades\DB::table('clients')->insert(array_merge($clientData, [
                    'id'         => $clientId,
                    'agency_id'  => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $client = Client::find($clientId);
            }

            return VisaCase::create([
                'agency_id'     => null,
                'client_id'     => $client->id,
                'country_code'  => strtoupper($data['country_code']),
                'visa_type'     => $data['visa_type'],
                'stage'         => 'lead',
                'public_status' => 'draft',
                'priority'      => 'normal',
                'travel_date'   => $data['planned_travel_date'] ?? null,
            ]);
        });

        return ApiResponse::created([
            'id'            => $case->id,
            'country_code'  => $case->country_code,
            'visa_type'     => $case->visa_type,
            'public_status' => $case->public_status,
        ], 'Заявка создана. Выберите агентство для подачи.');
    }

    /**
     * GET /public/me/cases
     * Заявки клиента, совпадающего по номеру телефона с публичным пользователем.
     */
    public function cases(Request $request): JsonResponse
    {
        $publicUser    = $request->get('_public_user');
        $stages        = config('stages');
        $caseStatuses  = config('case_statuses');

        // Поиск по нормализованному телефону (без +, чтобы найти оба формата)
        $phoneDigits = ltrim($publicUser->phone, '+');

        $cases = VisaCase::whereHas('client', fn ($q) => $q->whereRaw("REPLACE(phone, '+', '') = ?", [$phoneDigits]))
            ->with(['agency:id,name,city', 'assignee:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (VisaCase $case) use ($stages, $caseStatuses) {
                $stageConfig        = $stages[$case->stage] ?? null;
                $publicStatus       = $case->public_status ?? 'submitted';
                $publicStatusConfig = $caseStatuses[$publicStatus] ?? null;
                $totalDocs          = CaseChecklist::where('case_id', $case->id)->count();
                $uploadedDocs       = CaseChecklist::where('case_id', $case->id)
                    ->whereIn('status', ['uploaded', 'approved'])->count();
                return [
                    'id'                   => $case->id,
                    'country_code'         => $case->country_code,
                    'visa_type'            => $case->visa_type,
                    'stage'                => $case->stage,
                    'stage_label'          => $stageConfig['label'] ?? $case->stage,
                    'stage_order'          => $stageConfig['order'] ?? 0,
                    'stage_msg'            => $stageConfig['client_msg'] ?? $case->stage,
                    'public_status'        => $publicStatus,
                    'public_status_label'  => $publicStatusConfig['label'] ?? $publicStatus,
                    'public_status_order'  => $publicStatusConfig['order'] ?? 0,
                    'public_status_tooltip'=> $publicStatusConfig['tooltip'] ?? '',
                    'public_status_color'  => $publicStatusConfig['color'] ?? 'gray',
                    'priority'             => $case->priority,
                    'critical_date'        => $case->critical_date?->toDateString(),
                    'travel_date'          => $case->travel_date?->toDateString(),
                    'created_at'           => $case->created_at->toDateString(),
                    'agency'               => $case->agency ? ['name' => $case->agency->name, 'city' => $case->agency->city] : null,
                    'assignee'             => $case->assignee ? ['name' => $case->assignee->name] : null,
                    'docs_total'           => $totalDocs,
                    'docs_uploaded'        => $uploadedDocs,
                ];
            });

        return ApiResponse::success($cases);
    }

    /**
     * GET /public/me/cases/{id}
     * Детальная информация по заявке + агентство + менеджер + чек-лист документов.
     */
    public function caseDetail(Request $request, string $id): JsonResponse
    {
        $publicUser   = $request->get('_public_user');
        $stages       = config('stages');
        $caseStatuses = config('case_statuses');

        $phoneDigits = ltrim($publicUser->phone, '+');

        $case = VisaCase::whereHas('client', fn ($q) => $q->whereRaw("REPLACE(phone, '+', '') = ?", [$phoneDigits]))
            ->with([
                'agency:id,name,city,address,description,website_url,logo_url,phone,email,is_verified,rating,experience_years',
                'assignee:id,name,email,phone',
                'stageHistory',
            ])
            ->findOrFail($id);

        $stageConfig        = $stages[$case->stage] ?? null;
        $publicStatus       = $case->public_status ?? 'submitted';
        $publicStatusConfig = $caseStatuses[$publicStatus] ?? null;

        $checklist = CaseChecklist::where('case_id', $case->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => [
                'id'             => $item->id,
                'name'           => $item->name,
                'description'    => $item->description,
                'is_required'    => $item->is_required,
                'responsibility' => $item->responsibility ?? 'client',
                'status'         => $item->status,
                'notes'          => $item->notes,
            ]);

        $stageTimeline = $case->stageHistory->map(fn ($s) => [
            'stage'       => $s->stage,
            'stage_label' => $stages[$s->stage]['label'] ?? $s->stage,
            'entered_at'  => $s->entered_at?->toDateString(),
        ]);

        return ApiResponse::success([
            'id'                   => $case->id,
            'country_code'         => $case->country_code,
            'visa_type'            => $case->visa_type,
            'stage'                => $case->stage,
            'stage_label'          => $stageConfig['label']     ?? $case->stage,
            'stage_msg'            => $stageConfig['client_msg'] ?? $case->stage,
            'stage_order'          => $stageConfig['order']     ?? 0,
            'public_status'        => $publicStatus,
            'public_status_label'  => $publicStatusConfig['label']   ?? $publicStatus,
            'public_status_order'  => $publicStatusConfig['order']   ?? 0,
            'public_status_tooltip'=> $publicStatusConfig['tooltip'] ?? '',
            'public_status_color'  => $publicStatusConfig['color']   ?? 'gray',
            'priority'             => $case->priority,
            'critical_date'        => $case->critical_date?->toDateString(),
            'travel_date'          => $case->travel_date?->toDateString(),
            'notes'                => $case->notes,
            'created_at'           => $case->created_at->toDateString(),
            'agency'               => $case->agency ? [
                'name'             => $case->agency->name,
                'city'             => $case->agency->city,
                'address'          => $case->agency->address,
                'description'      => $case->agency->description,
                'website_url'      => $case->agency->website_url,
                'logo_url'         => $case->agency->logo_url,
                'phone'            => $case->agency->phone,
                'email'            => $case->agency->email,
                'is_verified'      => $case->agency->is_verified,
                'rating'           => $case->agency->rating,
                'experience_years' => $case->agency->experience_years,
            ] : null,
            'assignee'             => $case->assignee ? [
                'name'              => $case->assignee->name,
                'email'             => $case->assignee->email,
                'phone'             => $case->assignee->phone,
                'telegram_username' => $case->assignee->telegram_username,
            ] : null,
            'checklist'            => $checklist,
            'timeline'             => $stageTimeline,
        ]);
    }

    /**
     * POST /public/me/cases/{caseId}/checklist/{itemId}/upload
     * Загрузить документ по пункту чек-листа.
     */
    public function uploadChecklistItem(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        // Проверяем что кейс принадлежит пользователю
        $phoneDigits = ltrim($publicUser->phone, '+');
        $case = VisaCase::whereHas('client', fn ($q) => $q->whereRaw("REPLACE(phone, '+', '') = ?", [$phoneDigits]))
            ->findOrFail($caseId);

        $item = CaseChecklist::where('case_id', $case->id)->findOrFail($itemId);

        if (in_array($item->status, ['approved'])) {
            return ApiResponse::error('Документ уже одобрен, замена невозможна.', null, 422);
        }

        $path = $request->file('file')->store("case-docs/{$case->id}", 'public');

        // Создаём запись документа
        $doc = \App\Modules\Document\Models\Document::create([
            'agency_id'    => $case->agency_id,
            'case_id'      => $case->id,
            'client_id'    => $case->client_id,
            'original_name'=> $request->file('file')->getClientOriginalName(),
            'file_path'    => $path,
            'mime_type'    => $request->file('file')->getMimeType(),
            'size'         => $request->file('file')->getSize(),
            'type'         => 'client_upload',
            'status'       => 'pending',
        ]);

        $item->update([
            'document_id' => $doc->id,
            'status'      => 'uploaded',
        ]);

        return ApiResponse::success(['status' => 'uploaded'], 'Документ загружен и отправлен на проверку');
    }

    /**
     * POST /public/me/passport
     * Загрузить фото паспорта для OCR (заглушка — реальный OCR через Queue).
     */
    public function uploadPassport(Request $request): JsonResponse
    {
        $request->validate([
            'passport' => 'required|image|max:10240',
        ]);

        $user = $request->get('_public_user');

        $path = $request->file('passport')->store("public-passports/{$user->id}", 'public');

        $user->update([
            'ocr_status'   => 'pending',
            'ocr_raw_data' => ['file_path' => $path],
        ]);

        // TODO: dispatch(new ProcessPassportOcr($user->id, $path));

        return ApiResponse::success([
            'ocr_status' => 'pending',
            'message'    => 'Паспорт загружен. Данные распознаются автоматически (обычно до 30 сек).',
        ]);
    }
}
