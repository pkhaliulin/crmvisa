<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use App\Modules\PublicPortal\Models\PublicLead;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $agencies = Agency::where('is_active', true)
            ->whereNull('blocked_at')
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
                $q->with(['items.service:id,name_ru,name_en,category']);
            }])
            ->select([
                'id', 'name', 'city', 'address', 'latitude', 'longitude',
                'rating', 'reviews_count', 'description',
                'experience_years', 'website_url', 'logo_url', 'is_verified',
            ])
            ->orderByDesc('rating')
            ->get()
            ->filter(fn ($a) => $a->packages->isNotEmpty())
            ->map(fn ($a) => array_merge($a->toArray(), [
                'packages' => $a->packages->map(fn ($pkg) => [
                    'id'              => $pkg->id,
                    'name'            => $pkg->name,
                    'visa_type'       => $pkg->visa_type,
                    'description'     => $pkg->description,
                    'price'           => $pkg->price,
                    'currency'        => $pkg->currency ?? 'USD',
                    'processing_days' => $pkg->processing_days,
                    'services'        => $pkg->items->map(fn ($item) => [
                        'name'     => $item->service?->name_ru ?? $item->service?->name_en ?? '',
                        'category' => $item->service?->category ?? '',
                    ])->filter(fn ($s) => $s['name'])->values(),
                ]),
            ]))
            ->values();

        return ApiResponse::success(['agencies' => $agencies]);
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
        ]);

        $cc       = strtoupper($data['country_code']);
        $agencyId = $data['agency_id'];

        // Проверка дублей: заявка в то же агентство по той же стране
        $existing = PublicLead::where('public_user_id', $publicUser->id)
            ->where('country_code', $cc)
            ->where('assigned_agency_id', $agencyId)
            ->whereIn('status', ['new', 'contacted', 'assigned'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Вы уже отправили заявку в это агентство по данному направлению.',
                'lead_id' => $existing->id,
                'case_id' => $existing->case_id,
            ], 409);
        }

        $agency = Agency::findOrFail($agencyId);

        return DB::transaction(function () use ($publicUser, $agency, $data, $cc) {
            // 1. Найти или создать клиента в агентстве по номеру телефона
            $client = Client::firstOrCreate(
                ['phone' => $publicUser->phone, 'agency_id' => $agency->id],
                [
                    'name'   => $publicUser->name ?? ('Клиент ' . $publicUser->phone),
                    'source' => 'marketplace',
                ]
            );

            // Обновляем имя если оно теперь известно
            if ($publicUser->name && !$client->wasRecentlyCreated && !$client->name) {
                $client->update(['name' => $publicUser->name]);
            }

            // 2. Создать заявку
            $packageNote = $data['package_id']
                ? 'Выбранный пакет: ' . \DB::table('agency_service_packages')->where('id', $data['package_id'])->value('name')
                : null;

            $case = VisaCase::create([
                'agency_id'    => $agency->id,
                'client_id'    => $client->id,
                'country_code' => $cc,
                'visa_type'    => $data['visa_type'],
                'stage'        => 'lead',
                'priority'     => 'normal',
                'notes'        => implode("\n", array_filter([
                    'Лид с портала VisaBor.',
                    $packageNote,
                ])),
            ]);

            // 3. Получить скор по стране из кеша
            $score = (int) \DB::table('public_score_cache')
                ->where('public_user_id', $publicUser->id)
                ->where('country_code', $cc)
                ->value('score');

            // 4. Создать запись лида
            $lead = PublicLead::create([
                'public_user_id'     => $publicUser->id,
                'country_code'       => $cc,
                'visa_type'          => $data['visa_type'],
                'score'              => $score,
                'status'             => 'new',
                'assigned_agency_id' => $agency->id,
                'case_id'            => $case->id,
                'client_id'          => $client->id,
                'notes'              => $packageNote,
            ]);

            return ApiResponse::created([
                'lead_id' => $lead->id,
                'case_id' => $case->id,
            ], 'Заявка отправлена в агентство ' . $agency->name);
        });
    }
}
