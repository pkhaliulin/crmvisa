<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\PublicPortal\Models\PublicLead;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Modules\Payment\Models\ClientPayment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicAgencyController extends Controller
{
    /**
     * GET /public/agencies?country_code=ES&visa_type=tourist
     * Список агентств с пакетами для выбранной страны/типа визы.
     */
    public function index(Request $request): JsonResponse
    {
        $cc       = strtoupper($request->input('country_code', ''));
        $visaType = $request->input('visa_type', '');
        $locale   = $this->detectLocale($request);

        $agencies = Agency::where('is_active', true)
            ->whereNull('blocked_at')
            ->where(function ($q) {
                // Только агентства с активным планом (не истёк, или plan_expires_at = null)
                $q->whereNull('plan_expires_at')
                  ->orWhere('plan_expires_at', '>', now());
            })
            ->where(function ($q) use ($cc) {
                if ($cc) {
                    $q->whereHas('workCountries', fn ($wq) =>
                        $wq->where('country_code', $cc)->where('is_active', true)
                    );
                }
            })
            ->with(['packages' => function ($q) use ($cc, $visaType) {
                $q->where('is_active', true);
                if ($cc)       $q->where('country_code', $cc);
                if ($visaType) $q->where('visa_type', $visaType);
                $q->with(['items.service:id,name,category']);
            }])
            ->select([
                'id', 'name', 'city', 'address', 'latitude', 'longitude',
                'rating', 'reviews_count', 'top_criterion',
                'description', 'description_uz',
                'experience_years', 'website_url', 'logo_url', 'is_verified',
                'phone', 'email',
            ])
            ->orderByDesc('rating')
            ->get()
            ->filter(fn ($a) => $a->packages->isNotEmpty())
            ->map(fn ($a) => array_merge($a->only([
                'id', 'name', 'city', 'address', 'latitude', 'longitude',
                'rating', 'reviews_count', 'top_criterion',
                'experience_years', 'website_url', 'logo_url', 'is_verified',
                'phone', 'email',
            ]), [
                'description' => $this->localized($a, 'description', $locale),
                'packages' => $a->packages->map(fn ($pkg) => [
                    'id'              => $pkg->id,
                    'name'            => $this->localized($pkg, 'name', $locale),
                    'country_code'    => $pkg->country_code,
                    'visa_type'       => $pkg->visa_type,
                    'description'     => $this->localized($pkg, 'description', $locale),
                    'price'           => $pkg->price,
                    'currency'        => $pkg->currency ?? 'USD',
                    'processing_days' => $pkg->processing_days,
                    'services'        => $pkg->items->map(fn ($item) => [
                        'name'     => $item->service?->name ?? '',
                        'category' => $item->service?->category ?? '',
                    ])->filter(fn ($s) => $s['name'])->values(),
                ]),
            ]))
            ->values();

        return ApiResponse::success(['agencies' => $agencies]);
    }

    /**
     * GET /public/agencies/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $locale = $this->detectLocale($request);

        $agency = Agency::where('is_active', true)
            ->whereNull('blocked_at')
            ->findOrFail($id);

        // Пакеты услуг
        $packages = $agency->packages()
            ->where('is_active', true)
            ->with(['items.service:id,name,category'])
            ->get()
            ->map(fn ($pkg) => [
                'id'              => $pkg->id,
                'name'            => $this->localized($pkg, 'name', $locale),
                'country_code'    => $pkg->country_code,
                'visa_type'       => $pkg->visa_type,
                'description'     => $this->localized($pkg, 'description', $locale),
                'price'           => $pkg->price,
                'currency'        => $pkg->currency ?? 'USD',
                'processing_days' => $pkg->processing_days,
                'services'        => $pkg->items->map(fn ($item) => [
                    'name'     => $item->service?->name ?? '',
                    'category' => $item->service?->category ?? '',
                ])->filter(fn ($s) => $s['name'])->values(),
            ]);

        // Статистика (SET LOCAL для обхода RLS)
        $stats = DB::transaction(function () use ($id) {
            DB::statement("SET LOCAL app.current_tenant_id = '{$id}'");

            // Команда
            $team = DB::table('users')
                ->where('agency_id', $id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->select(['name', 'avatar_url', 'role'])
                ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get()
                ->map(fn ($u) => [
                    'name'       => $u->name,
                    'avatar_url' => $u->avatar_url,
                    'role'       => $u->role,
                ]);

            // Количество заявок
            $totalCases = DB::table('cases')
                ->where('agency_id', $id)
                ->whereNull('deleted_at')
                ->count();

            $completedCases = DB::table('cases')
                ->where('agency_id', $id)
                ->whereNull('deleted_at')
                ->whereIn('public_status', ['completed', 'rejected'])
                ->count();

            $approvedCases = DB::table('cases')
                ->where('agency_id', $id)
                ->whereNull('deleted_at')
                ->where('public_status', 'completed')
                ->count();

            // Активные заявки (сейчас в работе — не draft, не awaiting_payment, не completed, не rejected)
            $activeCases = DB::table('cases')
                ->where('agency_id', $id)
                ->whereNull('deleted_at')
                ->whereNotIn('public_status', ['draft', 'awaiting_payment', 'completed', 'rejected'])
                ->count();

            // Уникальные клиенты
            $clientsCount = DB::table('cases')
                ->where('agency_id', $id)
                ->whereNull('deleted_at')
                ->distinct('client_id')
                ->count('client_id');

            // Страны работы
            $countries = DB::table('agency_work_countries')
                ->where('agency_id', $id)
                ->where('is_active', true)
                ->pluck('country_code')
                ->toArray();

            // Типы виз (из пакетов)
            $visaTypes = DB::table('agency_service_packages')
                ->where('agency_id', $id)
                ->where('is_active', true)
                ->whereNotNull('visa_type')
                ->distinct()
                ->pluck('visa_type')
                ->toArray();

            return compact('team', 'totalCases', 'completedCases', 'approvedCases', 'activeCases', 'clientsCount', 'countries', 'visaTypes');
        });

        $successRate = $stats['completedCases'] > 0
            ? round(($stats['approvedCases'] / $stats['completedCases']) * 100)
            : null;

        return ApiResponse::success([
            'id'                => $agency->id,
            'name'              => $agency->name,
            'city'              => $agency->city,
            'address'           => $agency->address,
            'latitude'          => $agency->latitude,
            'longitude'         => $agency->longitude,
            'rating'            => $agency->rating,
            'reviews_count'     => $agency->reviews_count,
            'top_criterion'     => $agency->top_criterion,
            'description'       => $this->localized($agency, 'description', $locale),
            'experience_years'  => $agency->experience_years,
            'website_url'       => $agency->website_url,
            'logo_url'          => $agency->logo_url,
            'is_verified'       => $agency->is_verified,
            'phone'             => $agency->phone,
            'email'             => $agency->email,
            'member_since'      => $agency->created_at?->toDateString(),
            'packages'          => $packages,
            'team'              => $stats['team'],
            'team_count'        => $stats['team']->count(),
            'total_cases'       => $stats['totalCases'],
            'completed_cases'   => $stats['completedCases'],
            'approved_cases'    => $stats['approvedCases'],
            'success_rate'      => $successRate,
            'active_cases'      => $stats['activeCases'],
            'clients_count'     => $stats['clientsCount'],
            'countries'         => $stats['countries'],
            'visa_types'        => $stats['visaTypes'],
        ]);
    }

    private function detectLocale(Request $request): string
    {
        $lang = $request->input('lang') ?? $request->header('X-Locale');
        return in_array($lang, ['uz', 'ru']) ? $lang : 'ru';
    }

    private function localized($model, string $field, string $locale): ?string
    {
        if ($locale === 'uz') {
            $uzValue = $model->{$field . '_uz'};
            if ($uzValue) return $uzValue;
        }
        return $model->{$field};
    }

    /**
     * POST /public/leads
     * Клиент выбирает агентство → создаётся лид + клиент + заявка в CRM.
     */
    public function submitLead(Request $request): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $data = $request->validate([
            'agency_id'    => ['required', 'uuid', 'exists:agencies,id'],
            'country_code' => ['required', 'string', 'size:2'],
            'visa_type'    => ['required', 'string', 'max:50'],
            'package_id'   => ['nullable', 'uuid', 'exists:agency_service_packages,id'],
            'case_id'      => ['nullable', 'uuid', 'exists:cases,id'],
        ]);

        $cc       = strtoupper($data['country_code']);
        $agencyId = $data['agency_id'];

        // Проверка дублей — только при создании новой заявки (без case_id).
        // Если case_id передан, пользователь обновляет конкретный draft — не блокируем.
        if (empty($data['case_id'])) {
            $existingCase = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
                ->where('agency_id', $agencyId)
                ->where('country_code', $cc)
                ->whereIn('public_status', ['awaiting_payment', 'submitted', 'manager_assigned', 'document_collection'])
                ->first();

            if ($existingCase) {
                return response()->json([
                    'message' => __('public.lead_duplicate'),
                    'case_id' => $existingCase->id,
                ], 409);
            }
        }

        $agency = Agency::findOrFail($agencyId);

        return DB::transaction(function () use ($publicUser, $agency, $data, $cc) {
            // Устанавливаем tenant context для RLS (позволяет UPDATE agency_id из NULL в UUID)
            DB::statement("SET LOCAL app.current_tenant_id = '{$agency->id}'");

            // 1. Найти или создать клиента в агентстве
            $client = Client::where('public_user_id', $publicUser->id)
                ->where('agency_id', $agency->id)
                ->first();

            if (! $client) {
                $clientId = \Illuminate\Support\Str::uuid()->toString();
                DB::table('clients')->insert([
                    'id'             => $clientId,
                    'agency_id'      => $agency->id,
                    'public_user_id' => $publicUser->id,
                    'name'           => $publicUser->name ?? ('Клиент ' . $publicUser->phone),
                    'phone'          => app('encrypter')->encrypt($publicUser->phone),
                    'source'         => 'marketplace',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $client = Client::find($clientId);
            }

            // Обновляем имя если оно теперь известно
            if ($publicUser->name && !$client->wasRecentlyCreated && !$client->name) {
                $client->update(['name' => $publicUser->name]);
            }

            // 2. Создать или обновить заявку — ставим awaiting_payment (НЕ submitted)
            $packageId   = $data['package_id'] ?? null;
            $packageNote = $packageId
                ? 'Выбранный пакет: ' . \DB::table('agency_service_packages')->where('id', $packageId)->value('name')
                : null;

            // Если передан case_id — обновляем существующий DRAFT
            if (!empty($data['case_id'])) {
                $case = VisaCase::whereHas('client', fn ($q) => $q->where('public_user_id', $publicUser->id))
                    ->where('public_status', 'draft')
                    ->find($data['case_id']);

                if ($case) {
                    $case->update([
                        'agency_id'     => $agency->id,
                        'client_id'     => $client->id,
                        'public_status' => 'awaiting_payment',
                        'notes'         => implode("\n", array_filter([
                            'Лид с портала VisaBor.',
                            $packageNote,
                        ])),
                    ]);
                } else {
                    $case = null;
                }
            } else {
                $case = null;
            }

            if (! $case) {
                $case = VisaCase::create([
                    'agency_id'     => $agency->id,
                    'client_id'     => $client->id,
                    'country_code'  => $cc,
                    'visa_type'     => $data['visa_type'],
                    'stage'         => 'lead',
                    'public_status' => 'awaiting_payment',
                    'priority'      => 'normal',
                    'notes'         => implode("\n", array_filter([
                        'Лид с портала VisaBor.',
                        $packageNote,
                    ])),
                ]);
            }

            // Создать чек-лист документов, если ещё нет
            if (\DB::table('case_checklist')->where('case_id', $case->id)->doesntExist()) {
                app(ChecklistService::class)->createForCase($case);
            }

            // PublicLead НЕ создаётся здесь — создаётся после оплаты в ClientPaymentService

            // Создать pending-счёт, чтобы он отображался в "Счета и оплаты"
            $existingPayment = ClientPayment::where('case_id', $case->id)
                ->whereIn('status', ['pending', 'succeeded'])
                ->first();

            if (! $existingPayment) {
                $package = $packageId
                    ? DB::table('agency_service_packages')->where('id', $packageId)->first()
                    : DB::table('agency_service_packages')
                        ->where('agency_id', $agency->id)
                        ->where('country_code', $cc)
                        ->where('visa_type', $data['visa_type'])
                        ->where('is_active', true)
                        ->first();

                ClientPayment::create([
                    'case_id'        => $case->id,
                    'public_user_id' => $publicUser->id,
                    'agency_id'      => $agency->id,
                    'package_id'     => $package->id ?? null,
                    'amount'         => (int) ($package->price ?? 0),
                    'currency'       => $package->currency ?? 'USD',
                    'provider'       => 'pending',
                    'status'         => 'pending',
                ]);
            }

            return ApiResponse::created([
                'case_id' => $case->id,
            ], __('public.lead_sent', ['name' => $agency->name]));
        });
    }
}
