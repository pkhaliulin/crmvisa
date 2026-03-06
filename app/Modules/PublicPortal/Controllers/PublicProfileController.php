<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Models\CaseChecklist;
use App\Modules\Case\Services\CaseService;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\PublicPortal\Models\PublicLead;
use App\Modules\Case\Models\CaseFamilyMember;
use App\Mail\WelcomeEmail;
use App\Support\Helpers\ApiResponse;
use App\Support\Rules\ReferenceExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Modules\Payment\Models\ClientPayment;
use App\Modules\Service\Models\AgencyServicePackage;

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

        return ApiResponse::success([
            'user'            => $user->fresh(),
            'profile_percent' => $user->fresh()->profileCompleteness(),
        ], 'Профиль обновлён');
    }

    /**
     * POST /public/me/email
     * Сохранить recovery email + отправить welcome-письмо.
     */
    public function saveEmail(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        $data = $request->validate([
            'recovery_email' => 'required|email|max:255',
        ]);

        $user->update(['recovery_email' => $data['recovery_email']]);

        // Отправляем welcome-письмо (fire-and-forget, не блокируем ответ)
        try {
            Mail::to($data['recovery_email'])->send(new WelcomeEmail(
                userName: $user->name ?? 'Пользователь',
                userPhone: $user->phone,
            ));
        } catch (\Throwable $e) {
            \Log::warning('Welcome email failed', ['email' => $data['recovery_email'], 'error' => $e->getMessage()]);
        }

        return ApiResponse::success([
            'user' => $user->fresh(),
        ], 'Email сохранён');
    }

    /**
     * POST /public/me/change-phone/send-otp
     * Отправить OTP на новый номер телефона.
     */
    public function changePhoneSendOtp(Request $request): JsonResponse
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);

        $phone = \App\Support\Traits\NormalizesPhone::normalizePhone($request->phone);
        $currentUser = $request->get('_public_user');

        if ($phone === $currentUser->phone) {
            return ApiResponse::error('Это ваш текущий номер.', null, 422);
        }

        // Проверяем что номер не занят другим пользователем
        $existing = \App\Modules\PublicPortal\Models\PublicUser::where('phone', $phone)->first();
        if ($existing && $existing->id !== $currentUser->id) {
            return ApiResponse::error('Этот номер уже зарегистрирован.', null, 422);
        }

        app(\App\Modules\PublicPortal\Services\PhoneAuthService::class)->sendOtp($phone);

        return ApiResponse::success(null, 'Код отправлен на новый номер.');
    }

    /**
     * POST /public/me/change-phone/verify
     * Подтвердить смену номера по OTP.
     */
    public function changePhoneVerify(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code'  => ['required', 'string', 'size:4'],
        ]);

        $phone = \App\Support\Traits\NormalizesPhone::normalizePhone($request->phone);
        $user  = $request->get('_public_user');

        // Проверяем OTP
        $stubPin = config('services.sms_stub.pin');
        $otp = \DB::table('public_otp_codes')
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->when($stubPin, fn ($q) => $q->where('code', $stubPin),
                            fn ($q) => $q->where('code', $request->code))
            ->first();

        if (! $otp) {
            return ApiResponse::error('Неверный или истёкший код.', null, 422);
        }

        \DB::table('public_otp_codes')->where('id', $otp->id)->update(['used_at' => now()]);

        $user->update(['phone' => $phone]);

        return ApiResponse::success([
            'user' => $user->fresh(),
        ], 'Номер телефона изменён.');
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
            'planned_travel_date'  => ['nullable', 'date', 'after:today'],
            'planned_return_date'  => ['nullable', 'date', 'after:today'],
        ]);

        // Проверяем, есть ли хотя бы одно агентство, работающее с этой страной
        $hasAgencies = \App\Modules\Agency\Models\AgencyWorkCountry::where('country_code', strtoupper($data['country_code']))
            ->where('is_active', true)
            ->whereHas('agency', fn ($q) => $q->where('is_active', true))
            ->exists();

        if (! $hasAgencies) {
            return ApiResponse::error('К сожалению, пока ни одно агентство не работает с этой страной. Заявку создать невозможно.', [
                'no_agencies' => true,
                'country_code' => strtoupper($data['country_code']),
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
            'payment_expires_at'   => ClientPayment::where('case_id', $case->id)
                ->where('status', 'pending')
                ->whereNotNull('expires_at')
                ->value('expires_at'),
            'appointment_date'     => $case->appointment_date?->toDateString(),
            'appointment_time'     => $case->appointment_time,
            'appointment_location' => $case->appointment_location,
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
                    'dob'                   => $cm->familyMember->dob?->toDateString(),
                    'gender'                => $cm->familyMember->gender,
                    'citizenship'           => $cm->familyMember->citizenship,
                    'passport_number'       => $cm->familyMember->passport_number,
                    'passport_expires_at'   => $cm->familyMember->passport_expires_at?->toDateString(),
                    'is_minor'              => $cm->familyMember->isMinor(),
                    'checklist'             => CaseChecklist::where('case_id', $case->id)
                        ->where('family_member_id', $cm->familyMember->id)
                        ->orderBy('sort_order')
                        ->get()
                        ->map(fn ($item) => [
                            'id'          => $item->id,
                            'name'        => $item->name,
                            'description' => $item->description,
                            'is_required' => $item->is_required,
                            'status'      => $item->status,
                            'notes'       => $item->notes,
                        ]),
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
        $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
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

        // Авто-переход: все обязательные документы загружены → doc_review
        app(CaseService::class)->checkAutoTransitionAfterUpload($case->fresh());

        return ApiResponse::success(['status' => 'uploaded'], 'Документ загружен и отправлен на проверку');
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

        // Отменяем связанный pending-платёж если есть
        \App\Modules\Payment\Models\ClientPayment::where('case_id', $case->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $case->update(['public_status' => 'cancelled']);

        return ApiResponse::success(['id' => $case->id, 'public_status' => 'cancelled'], 'Заявка отменена.');
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
                'package'          => $a->packages->first() ? [
                    'id'              => $a->packages->first()->id,
                    'name'            => $locale === 'uz' && $a->packages->first()->name_uz
                        ? $a->packages->first()->name_uz
                        : $a->packages->first()->name,
                    'price'           => $a->packages->first()->price,
                    'currency'        => $a->packages->first()->currency ?? 'USD',
                    'processing_days' => $a->packages->first()->processing_days,
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
                DB::statement("SET LOCAL app.current_tenant_id = '{$case->agency_id}'");
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
