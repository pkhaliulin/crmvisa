<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\CaseFamilyMember;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\Payment\Models\ClientPayment;
use App\Modules\PublicPortal\Models\PublicLead;
use App\Modules\Service\Models\AgencyServicePackage;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicCaseController extends Controller
{
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
            'planned_travel_date'  => ['nullable', 'date', 'after:today'],
            'planned_return_date'  => ['nullable', 'date', 'after:today'],
            'travelers_count'      => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ]);

        // Проверяем, есть ли хотя бы одно агентство с пакетом услуг для этой страны и типа визы
        $countryCode = strtoupper($data['country_code']);
        $visaType = $data['visa_type'];

        $hasAgencies = \App\Modules\Agency\Models\AgencyWorkCountry::where('country_code', $countryCode)
            ->where('is_active', true)
            ->whereHas('agency', fn ($q) => $q->where('is_active', true)->whereNull('blocked_at'))
            ->exists();

        if (! $hasAgencies) {
            return ApiResponse::error('К сожалению, пока ни одно агентство не работает с этой страной. Заявку создать невозможно.', [
                'no_agencies' => true,
                'country_code' => $countryCode,
            ], 422);
        }

        $hasPackage = DB::table('agency_service_packages')
            ->where('country_code', $countryCode)
            ->where('visa_type', $visaType)
            ->where('is_active', true)
            ->whereIn('agency_id', function ($q) {
                $q->select('id')->from('agencies')
                  ->where('is_active', true)
                  ->whereNull('blocked_at');
            })
            ->exists();

        if (! $hasPackage) {
            return ApiResponse::error('Ни одно агентство пока не предлагает этот тип визы для выбранной страны.', [
                'no_packages' => true,
                'country_code' => $countryCode,
                'visa_type' => $visaType,
            ], 422);
        }

        $case = DB::transaction(function () use ($publicUser, $data) {
            $client = Client::withoutTenant()
                ->where('public_user_id', $publicUser->id)
                ->whereNull('agency_id')
                ->first();

            if (! $client) {
                $clientId = \Illuminate\Support\Str::uuid()->toString();
                DB::table('clients')->insert([
                    'id'             => $clientId,
                    'agency_id'      => null,
                    'public_user_id' => $publicUser->id,
                    'name'           => $publicUser->name ?? ('Клиент ' . $publicUser->phone),
                    'phone'          => app('encrypter')->encrypt($publicUser->phone),
                    'source'         => 'marketplace',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $client = Client::find($clientId);
            }

            $noteParts = [];
            if (!empty($data['travelers_count']) && $data['travelers_count'] > 1) {
                $noteParts[] = "Путешественников: {$data['travelers_count']}";
            }
            if (!empty($data['notes'])) {
                $noteParts[] = $data['notes'];
            }

            $case = VisaCase::create([
                'agency_id'     => null,
                'client_id'     => $client->id,
                'country_code'  => strtoupper($data['country_code']),
                'visa_type'     => $data['visa_type'],
                'stage'         => 'lead',
                'public_status' => 'draft',
                'priority'      => 'normal',
                'travel_date'   => $data['planned_travel_date'] ?? null,
                'return_date'   => $data['planned_return_date'] ?? null,
                'notes'         => $noteParts ? implode("\n", $noteParts) : null,
            ]);

            app(ChecklistService::class)->createForCase($case);

            return $case;
        });

        return ApiResponse::created([
            'id'            => $case->id,
            'case_number'   => $case->case_number,
            'country_code'  => $case->country_code,
            'visa_type'     => $case->visa_type,
            'public_status' => $case->public_status,
            'travel_date'   => $case->travel_date?->toDateString(),
            'return_date'   => $case->return_date?->toDateString(),
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

        $cases = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->with(['agency:id,name,city', 'assignee:id,name,phone,telegram_username', 'stageHistory'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (VisaCase $case) use ($stages, $caseStatuses) {
                $stageConfig        = $stages[$case->stage] ?? null;
                $publicStatus       = $case->public_status ?? 'submitted';
                $publicStatusConfig = $caseStatuses[$publicStatus] ?? null;
                $totalDocs          = CaseChecklist::where('case_id', $case->id)->count();
                $uploadedDocs       = CaseChecklist::where('case_id', $case->id)
                    ->whereIn('status', ['uploaded', 'approved'])->count();

                // SLA deadline текущего этапа
                $currentStage = $case->stageHistory
                    ->where('stage', $case->stage)
                    ->whereNull('exited_at')
                    ->last();

                return [
                    'id'                   => $case->id,
                    'case_number'          => $case->case_number,
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
                    'return_date'          => $case->return_date?->toDateString(),
                    'created_at'           => $case->created_at->toDateString(),
                    'agency'               => $case->agency ? ['name' => $case->agency->name, 'city' => $case->agency->city] : null,
                    'assignee'             => $case->assignee ? [
                        'name'              => $case->assignee->name,
                        'phone'             => $case->assignee->phone,
                        'telegram_username' => $case->assignee->telegram_username,
                    ] : null,
                    'docs_total'           => $totalDocs,
                    'docs_uploaded'        => $uploadedDocs,
                    'payment_status'       => $case->payment_status ?? 'unpaid',
                    'payment_deadline'     => $currentStage?->sla_due_at?->toIso8601String(),
                    'appointment_date'     => $case->appointment_date?->toDateString(),
                    'appointment_time'     => $case->appointment_time,
                    'appointment_location' => $case->appointment_location,
                    'reviewed_at'          => $case->reviewed_at?->toDateTimeString(),
                    'needs_review'         => in_array($case->public_status, ['completed', 'rejected']) && ! $case->reviewed_at,
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

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->with([
                'agency:id,name,city,address,description,website_url,logo_url,phone,email,is_verified,rating,experience_years',
                'assignee:id,name,email,phone,telegram_username',
                'stageHistory',
            ])
            ->findOrFail($id);

        $stageConfig        = $stages[$case->stage] ?? null;
        $publicStatus       = $case->public_status ?? 'submitted';
        $publicStatusConfig = $caseStatuses[$publicStatus] ?? null;

        $checklist = CaseChecklist::where('case_id', $case->id)
            ->whereNull('family_member_id')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($item) => $this->mapChecklistItem($item));

        $stageTimeline = $case->stageHistory->map(fn ($s) => [
            'stage'       => $s->stage,
            'stage_label' => $stages[$s->stage]['label'] ?? $s->stage,
            'entered_at'  => $s->entered_at?->toDateString(),
        ]);

        // Пакет услуг (для отображения цены и состава)
        $package = null;
        if ($case->agency_id) {
            $pkg = AgencyServicePackage::withoutTenant()
                ->where('agency_id', $case->agency_id)
                ->where('country_code', $case->country_code)
                ->where('visa_type', $case->visa_type)
                ->where('is_active', true)
                ->with(['items.service:id,name,category'])
                ->first();

            if ($pkg) {
                $locale = $request->input('lang') ?? $request->header('X-Locale', 'ru');
                $locale = in_array($locale, ['uz', 'ru']) ? $locale : 'ru';
                $package = [
                    'id'              => $pkg->id,
                    'name'            => ($locale === 'uz' && $pkg->name_uz) ? $pkg->name_uz : $pkg->name,
                    'description'     => ($locale === 'uz' && $pkg->description_uz) ? $pkg->description_uz : $pkg->description,
                    'price'           => $pkg->price,
                    'currency'        => $pkg->currency ?? 'USD',
                    'processing_days' => $pkg->processing_days,
                    'services'        => $pkg->items->map(fn ($item) => [
                        'name'     => $item->service?->name ?? '',
                        'category' => $item->service?->category ?? '',
                    ])->filter(fn ($s) => $s['name'])->values(),
                ];
            }
        }

        // Пересчёт pending-платежа (catch-up)
        $pendingPayment = ClientPayment::where('case_id', $case->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingPayment && empty($pendingPayment->metadata['price_breakdown'])) {
            \App\Modules\Payment\Services\ClientPaymentService::recalculatePaymentAmount($pendingPayment);
            $pendingPayment->refresh();
        }

        return ApiResponse::success([
            'id'                   => $case->id,
            'case_number'          => $case->case_number,
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
            'return_date'          => $case->return_date?->toDateString(),
            'payment_status'       => $case->payment_status ?? 'unpaid',
            'payment_expires_at'   => $pendingPayment?->expires_at,
            'payment_amount'       => $pendingPayment?->amount,
            'payment_currency'     => $pendingPayment?->currency,
            'price_breakdown'      => $pendingPayment?->metadata['price_breakdown'] ?? null,
            'appointment_date'     => $case->appointment_date?->toDateString(),
            'appointment_time'     => $case->appointment_time,
            'appointment_location' => $case->appointment_location,
            'reviewed_at'          => $case->reviewed_at?->toDateTimeString(),
            'needs_review'         => in_array($case->public_status, ['completed', 'rejected']) && ! $case->reviewed_at,
            'group_id'             => $case->group_id,
            'notes'                => $case->notes,
            'max_stay_days'        => \DB::table('country_visa_type_settings')
                ->where('country_code', $case->country_code)
                ->where('visa_type', $case->visa_type)
                ->value('max_stay_days'),
            'deadline_info'        => VisaCase::deadlineExplanation($case->country_code, $case->visa_type),
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
            'package'              => $package,
            'checklist'            => $checklist,
            'timeline'             => $stageTimeline,
            'family_members'       => CaseFamilyMember::where('case_id', $case->id)
                ->with('familyMember')
                ->get()
                ->map(fn ($cm) => [
                    'id'                    => $cm->familyMember->id,
                    'case_family_member_id' => $cm->id,
                    'name'                  => $cm->familyMember->name,
                    'relationship'          => $cm->familyMember->relationship,
                    'dob'                   => $this->toDateStr($cm->familyMember->dob),
                    'gender'                => $cm->familyMember->gender,
                    'citizenship'           => $cm->familyMember->citizenship,
                    'passport_number'       => $cm->familyMember->passport_number,
                    'passport_expires_at'   => $this->toDateStr($cm->familyMember->passport_expires_at),
                    'is_minor'              => $cm->familyMember->isMinor(),
                    'checklist'             => CaseChecklist::where('case_id', $case->id)
                        ->where('family_member_id', $cm->familyMember->id)
                        ->orderBy('sort_order')
                        ->get()
                        ->map(fn ($item) => $this->mapChecklistItem($item)),
                ]),
        ]);
    }

    /**
     * PATCH /public/me/cases/{id}
     * Обновить данные заявки (travel_date и т.д.) — только для draft / awaiting_payment.
     */
    public function updateCase(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($id);

        $data = $request->validate([
            'travel_date'  => ['sometimes', 'nullable', 'date', 'after:today'],
            'return_date'  => ['sometimes', 'nullable', 'date', 'after:today'],
        ]);

        $case->update($data);

        return ApiResponse::success([
            'travel_date'   => $case->travel_date?->toDateString(),
            'return_date'   => $case->return_date?->toDateString(),
            'critical_date' => $case->critical_date?->toDateString(),
            'deadline_info' => VisaCase::deadlineExplanation($case->country_code, $case->visa_type),
        ], 'Обновлено');
    }

    /**
     * POST /public/me/cases/{id}/cancel
     * Отмена заявки клиентом (только draft и awaiting_payment).
     */
    public function cancelCase(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->where('id', $id)
            ->firstOrFail();

        $cancellable = ['draft', 'awaiting_payment'];

        if (! in_array($case->public_status, $cancellable)) {
            return ApiResponse::error(
                'Отменить можно только заявки в статусе «Черновик» или «Ожидание оплаты». Свяжитесь с агентством для отмены.',
                ['public_status' => $case->public_status],
                422
            );
        }

        // Делегируем в CaseService (SSOT) — обновит stage, public_status, платежи, историю
        $case = app(CaseService::class)->cancelCase($case, 'Отменено клиентом');

        \App\Support\Helpers\AuditLog::log('case.cancelled', [
            'case_id' => $case->id,
            'agency_id' => $case->agency_id,
            'public_user_id' => $publicUser->id,
        ]);

        return ApiResponse::success(['id' => $case->id, 'public_status' => 'cancelled'], 'Заявка отменена.');
    }

    /**
     * POST /public/me/cases/{caseId}/checklist/{itemId}/upload
     * Загрузить документ по пункту чек-листа.
     */
    public function uploadChecklistItem(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx', new \App\Rules\SafeFileName],
        ]);

        // Проверяем что кейс принадлежит пользователю
        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($caseId);

        $item = CaseChecklist::where('case_id', $case->id)->findOrFail($itemId);

        if (in_array($item->status, ['approved'])) {
            return ApiResponse::error('Документ уже одобрен, замена невозможна.', null, 422);
        }

        $path = $request->file('file')->store("agencies/{$case->agency_id}/cases/{$case->id}", 'documents');

        // Создаём запись документа
        $doc = Document::create([
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

        // Авто-переход: все обязательные документы загружены -> doc_review
        app(CaseService::class)->checkAutoTransitionAfterUpload($case->fresh());

        return ApiResponse::success(['status' => 'uploaded'], 'Документ загружен и отправлен на проверку');
    }

    /**
     * PATCH /public/me/cases/{caseId}/checklist/{itemId}/check
     * Отметить / снять отметку checkbox-слота (Фото 3x4 и т.п.)
     */
    public function checkChecklistItem(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($caseId);

        $item = CaseChecklist::where('case_id', $case->id)->findOrFail($itemId);

        // Смена статуса на not_available / pending (для любого типа документа)
        if ($request->has('status') && in_array($request->input('status'), ['not_available', 'pending'])) {
            $newStatus = $request->input('status');
            $item->update(['status' => $newStatus]);

            return ApiResponse::success(['status' => $newStatus, 'is_checked' => (bool) $item->is_checked]);
        }

        // Checkbox toggle
        $request->validate(['checked' => ['required', 'boolean']]);

        if ($item->type !== 'checkbox') {
            return ApiResponse::error('Этот документ требует загрузки файла', null, 422);
        }

        $checked = $request->boolean('checked');
        $item->update([
            'is_checked' => $checked,
            'status'     => $checked ? 'uploaded' : 'pending',
        ]);

        app(CaseService::class)->checkAutoTransitionAfterUpload($case->fresh());

        return ApiResponse::success([
            'status'     => $item->fresh()->status,
            'is_checked' => $checked,
        ]);
    }

    /**
     * POST /public/me/cases/{caseId}/checklist/{itemId}/repeat
     * Добавить ещё один слот для повторяемого документа (метрика ребёнка, старый паспорт и т.д.)
     */
    public function repeatChecklistItem(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($caseId);

        $item = CaseChecklist::where('case_id', $case->id)->findOrFail($itemId);

        if (! $item->is_repeatable) {
            return ApiResponse::error('Этот документ не является повторяемым', null, 422);
        }

        // Ограничение: не более 10 копий одного документа
        $existingCount = CaseChecklist::where('case_id', $case->id)
            ->where('name', $item->name)
            ->where('family_member_id', $item->family_member_id)
            ->count();

        if ($existingCount >= 10) {
            return ApiResponse::error('Максимум 10 копий одного документа', null, 422);
        }

        $maxOrder = CaseChecklist::where('case_id', $case->id)->max('sort_order') ?? 0;

        $newItem = CaseChecklist::create([
            'agency_id'              => $case->agency_id,
            'case_id'                => $case->id,
            'family_member_id'       => $item->family_member_id,
            'country_requirement_id' => $item->country_requirement_id,
            'requirement_id'         => $item->requirement_id,
            'type'                   => $item->type,
            'name'                   => $item->name,
            'description'            => $item->description,
            'is_required'            => false, // доп. копии не обязательны
            'is_repeatable'          => true,
            'responsibility'         => $item->responsibility,
            'status'                 => 'pending',
            'sort_order'             => $item->sort_order + 1,
        ]);

        return ApiResponse::created([
            'id'            => $newItem->id,
            'name'          => $newItem->name,
            'description'   => $newItem->description,
            'type'          => $newItem->type,
            'is_required'   => false,
            'is_checked'    => false,
            'is_repeatable' => true,
            'responsibility'=> $newItem->responsibility,
            'status'        => 'pending',
            'document_id'   => null,
            'notes'         => null,
        ], 'Добавлен дополнительный слот');
    }

    /**
     * DELETE /public/me/cases/{caseId}/checklist/{itemId}
     * Удалить повторный слот чеклиста (только не-обязательные копии).
     */
    public function deleteChecklistItem(Request $request, string $caseId, string $itemId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($caseId);

        $item = CaseChecklist::where('case_id', $case->id)->findOrFail($itemId);

        // Нельзя удалять обязательные оригинальные документы
        if ($item->is_required) {
            return ApiResponse::error('Нельзя удалить обязательный документ', null, 422);
        }

        // Удалить связанный документ файл если есть
        if ($item->document_id) {
            $doc = Document::find($item->document_id);
            if ($doc && $doc->file_path) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($doc->file_path);
            }
            $doc?->delete();
        }

        $item->forceDelete();

        return ApiResponse::success(['message' => 'Слот удалён']);
    }

    /**
     * GET /public/me/documents/{documentId}/preview
     * Временная ссылка на документ для предпросмотра.
     */
    public function previewDocument(Request $request, string $documentId): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $document = Document::whereHas('case', function ($q) use ($publicUser) {
            $q->whereHas('client', fn ($c) => $c->where('public_user_id', $publicUser->id));
        })->findOrFail($documentId);

        return ApiResponse::success([
            'url'           => $document->url,
            'original_name' => $document->original_name,
            'mime_type'     => $document->mime_type,
            'size'          => $document->size,
        ]);
    }

    /**
     * GET /public/me/cases/{id}/agencies
     * Агентства, работающие со страной кейса.
     */
    public function caseAgencies(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');
        $locale     = $request->input('lang') ?? $request->header('X-Locale', 'ru');
        $locale     = in_array($locale, ['uz', 'ru']) ? $locale : 'ru';

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($id);

        $cc       = $case->country_code;
        $visaType = $case->visa_type;

        $agencies = Agency::where('is_active', true)
            ->whereNull('blocked_at')
            ->whereHas('workCountries', fn ($wq) =>
                $wq->where('country_code', $cc)->where('is_active', true)
            )
            ->with(['packages' => function ($q) use ($cc, $visaType) {
                $q->where('is_active', true);
                if ($cc)       $q->where('country_code', $cc);
                if ($visaType) $q->where('visa_type', $visaType);
            }])
            ->select([
                'id', 'name', 'city', 'rating', 'reviews_count',
                'description', 'description_uz',
                'experience_years', 'logo_url', 'is_verified',
            ])
            ->orderByDesc('rating')
            ->get()
            ->filter(fn ($a) => $a->packages->isNotEmpty())
            ->map(fn ($a) => [
                'id'               => $a->id,
                'name'             => $a->name,
                'city'             => $a->city,
                'rating'           => $a->rating,
                'reviews_count'    => $a->reviews_count,
                'experience_years' => $a->experience_years,
                'logo_url'         => $a->logo_url,
                'is_verified'      => $a->is_verified,
                'description'      => $locale === 'uz' && $a->description_uz ? $a->description_uz : $a->description,
                'package'          => ($pkg = $a->packages->first()) ? [
                    'id'              => $pkg->id,
                    'name'            => $locale === 'uz' && $pkg->name_uz
                        ? $pkg->name_uz
                        : $pkg->name,
                    'price'           => $pkg->price,
                    'currency'        => $pkg->currency ?? 'USD',
                    'processing_days' => $pkg->processing_days,
                ] : null,
            ])
            ->values();

        return ApiResponse::success(['agencies' => $agencies]);
    }

    /**
     * POST /public/me/cases/{id}/change-agency
     * Сменить агентство (до оплаты).
     */
    public function changeAgency(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
            ->findOrFail($id);

        if (($case->payment_status ?? 'unpaid') === 'paid') {
            return ApiResponse::error('Невозможно сменить агентство после оплаты.', null, 403);
        }

        DB::transaction(function () use ($case, $publicUser) {
            // Устанавливаем tenant context для RLS (позволяет UPDATE agency_id)
            if ($case->agency_id) {
                DB::unprepared("SET LOCAL app.current_tenant_id = '{$case->agency_id}'");
            }

            // Архивируем старые лиды
            PublicLead::where('case_id', $case->id)
                ->whereIn('status', ['new', 'contacted', 'assigned'])
                ->update(['status' => 'cancelled']);

            // Возвращаем кейс в draft
            $case->update([
                'agency_id'      => null,
                'public_status'  => 'draft',
                'payment_status' => 'unpaid',
            ]);
        });

        return ApiResponse::success(['message' => 'Агентство откреплено. Выберите новое.']);
    }

    private function mapChecklistItem(CaseChecklist $item): array
    {
        $doc = $item->document_id ? Document::find($item->document_id) : null;

        return [
            'id'               => $item->id,
            'name'             => $item->name,
            'description'      => $item->description,
            'type'             => $item->type ?? 'upload',
            'is_required'      => $item->is_required,
            'is_checked'       => (bool) $item->is_checked,
            'is_repeatable'    => (bool) $item->is_repeatable,
            'is_from_template' => (bool) ($item->country_requirement_id || $item->requirement_id),
            'responsibility'   => $item->responsibility ?? 'client',
            'status'           => $item->status,
            'document_id'      => $item->document_id,
            'notes'            => $item->notes,
            'file_name'        => $doc?->original_name,
            'file_url'         => $doc?->url,
            'file_mime'        => $doc?->mime_type,
        ];
    }

    private function toDateStr(mixed $val): ?string
    {
        if ($val === null) return null;
        if ($val instanceof \DateTimeInterface) return $val->format('Y-m-d');
        return is_string($val) ? $val : (string) $val;
    }
}
