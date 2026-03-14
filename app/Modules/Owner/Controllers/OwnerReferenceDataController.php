<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Models\DocumentTemplate;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OwnerReferenceDataController extends Controller
{
    // =========================================================================
    // Страны (portal_countries)
    // =========================================================================

    public function countries(Request $request): JsonResponse
    {
        $query = DB::table('portal_countries')
            ->when($request->visa_regime, fn ($q, $r) => $q->where('visa_regime', $r))
            ->when($request->continent, fn ($q, $c) => $q->where('continent', $c))
            ->when($request->boolean('is_popular'), fn ($q) => $q->where('is_popular', true))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->orderBy('sort_order');

        $countries = $query->get()->map(function ($c) {
            $c->visa_types = is_string($c->visa_types) ? json_decode($c->visa_types, true) : ($c->visa_types ?? []);
            return $c;
        });
        return ApiResponse::success($countries);
    }

    public function countryUpdate(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'name'                       => 'sometimes|string|max:100',
            'name_uz'                    => 'sometimes|nullable|string|max:100',
            'flag_emoji'                 => 'sometimes|string|max:10',
            'is_active'                  => 'sometimes|boolean',
            'weight_finance'             => 'sometimes|numeric|min:0|max:1',
            'weight_ties'                => 'sometimes|numeric|min:0|max:1',
            'weight_travel'              => 'sometimes|numeric|min:0|max:1',
            'weight_profile'             => 'sometimes|numeric|min:0|max:1',
            'min_monthly_income_usd'     => 'sometimes|integer|min:0',
            'min_score'                  => 'sometimes|integer|min:0|max:100',
            'sort_order'                 => 'sometimes|integer|min:0',
            'visa_types'                 => 'sometimes|array',
            'visa_types.*'               => 'string|max:50',
            // Посольство
            'embassy_website'            => 'sometimes|nullable|url|max:500',
            'appointment_url'            => 'sometimes|nullable|url|max:500',
            'embassy_description'        => 'sometimes|nullable|string|max:5000',
            'embassy_rules'              => 'sometimes|nullable|string|max:5000',
            // Сроки обработки
            'processing_days_standard'   => 'sometimes|nullable|integer|min:0',
            'processing_days_expedited'  => 'sometimes|nullable|integer|min:0',
            'appointment_wait_days'      => 'sometimes|nullable|integer|min:0',
            'buffer_days_recommended'    => 'sometimes|nullable|integer|min:0',
            // Визовый режим
            'visa_regime'                => 'sometimes|in:visa_free,visa_on_arrival,evisa,visa_required',
            'visa_free_days'             => 'sometimes|nullable|integer|min:0',
            'visa_on_arrival_days'       => 'sometimes|nullable|integer|min:0',
            'evisa_available'            => 'sometimes|boolean',
            'evisa_url'                  => 'sometimes|nullable|url|max:500',
            'evisa_processing_days'      => 'sometimes|nullable|integer|min:0',
            // Требования
            'invitation_required'        => 'sometimes|boolean',
            'hotel_booking_required'     => 'sometimes|boolean',
            'insurance_required'         => 'sometimes|boolean',
            'bank_statement_required'    => 'sometimes|boolean',
            'return_ticket_required'     => 'sometimes|boolean',
            // Стоимости
            'visa_fee_usd'               => 'sometimes|nullable|numeric|min:0',
            'evisa_fee_usd'              => 'sometimes|nullable|numeric|min:0',
            'avg_flight_cost_usd'        => 'sometimes|nullable|integer|min:0',
            'avg_hotel_per_night_usd'    => 'sometimes|nullable|integer|min:0',
            // Флаги
            'is_popular'                 => 'sometimes|boolean',
            'is_high_approval'           => 'sometimes|boolean',
            'is_high_refusal'            => 'sometimes|boolean',
            // Доп.
            'notes'                      => 'sometimes|nullable|string|max:5000',
            'continent'                  => 'sometimes|nullable|string|max:30',
        ]);

        if (isset($data['visa_types'])) {
            $data['visa_types'] = json_encode($data['visa_types']);
        }

        $data['updated_at'] = now();
        DB::table('portal_countries')->where('country_code', $code)->update($data);

        Cache::forget('portal_countries_weights');
        Cache::forget('portal_countries_codes');

        $row = DB::table('portal_countries')->where('country_code', $code)->first();
        $row->visa_types = is_string($row->visa_types) ? json_decode($row->visa_types, true) : [];
        return ApiResponse::success($row);
    }

    public function countryStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_code'            => 'required|string|size:2|uppercase',
            'name'                    => 'required|string|max:100',
            'name_uz'                 => 'sometimes|nullable|string|max:100',
            'flag_emoji'              => 'sometimes|string|max:10',
            'is_active'               => 'sometimes|boolean',
            'weight_finance'          => 'sometimes|numeric|min:0|max:1',
            'weight_ties'             => 'sometimes|numeric|min:0|max:1',
            'weight_travel'           => 'sometimes|numeric|min:0|max:1',
            'weight_profile'          => 'sometimes|numeric|min:0|max:1',
            'min_monthly_income_usd'  => 'sometimes|integer|min:0',
            'min_score'               => 'sometimes|integer|min:0|max:100',
            'visa_types'              => 'sometimes|array',
            'visa_types.*'            => 'string|max:50',
            // Визовый режим
            'visa_regime'             => 'sometimes|in:visa_free,visa_on_arrival,evisa,visa_required',
            'visa_free_days'          => 'sometimes|nullable|integer|min:0',
            'visa_on_arrival_days'    => 'sometimes|nullable|integer|min:0',
            'evisa_available'         => 'sometimes|boolean',
            'evisa_url'               => 'sometimes|nullable|url|max:500',
            'evisa_processing_days'   => 'sometimes|nullable|integer|min:0',
            // Требования
            'invitation_required'     => 'sometimes|boolean',
            'hotel_booking_required'  => 'sometimes|boolean',
            'insurance_required'      => 'sometimes|boolean',
            'bank_statement_required' => 'sometimes|boolean',
            'return_ticket_required'  => 'sometimes|boolean',
            // Стоимости
            'visa_fee_usd'            => 'sometimes|nullable|numeric|min:0',
            'evisa_fee_usd'           => 'sometimes|nullable|numeric|min:0',
            'avg_flight_cost_usd'     => 'sometimes|nullable|integer|min:0',
            'avg_hotel_per_night_usd' => 'sometimes|nullable|integer|min:0',
            // Флаги
            'is_popular'              => 'sometimes|boolean',
            'is_high_approval'        => 'sometimes|boolean',
            'is_high_refusal'         => 'sometimes|boolean',
            // Доп.
            'notes'                   => 'sometimes|nullable|string|max:5000',
            'continent'               => 'sometimes|nullable|string|max:30',
        ]);

        abort_if(DB::table('portal_countries')->where('country_code', $data['country_code'])->exists(), 422,
            'Страна уже существует');

        $data['visa_types'] = json_encode($data['visa_types'] ?? ['tourist', 'student', 'business']);

        DB::table('portal_countries')->insert(array_merge([
            'weight_finance'         => 0.25,
            'weight_ties'            => 0.25,
            'weight_travel'          => 0.25,
            'weight_profile'         => 0.25,
            'min_monthly_income_usd' => 1000,
            'min_score'              => 60,
        ], $data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        Cache::forget('portal_countries_weights');
        Cache::forget('portal_countries_codes');

        $row = DB::table('portal_countries')->where('country_code', $data['country_code'])->first();
        $row->visa_types = json_decode($row->visa_types, true);
        return ApiResponse::success($row, 'Страна добавлена', 201);
    }

    // =========================================================================
    // Типы виз (portal_visa_types)
    // =========================================================================

    public function visaTypes(): JsonResponse
    {
        $types = DB::table('portal_visa_types')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($t) {
                $t->settings_count = DB::table('country_visa_type_settings')
                    ->where('visa_type', $t->slug)->count();
                return $t;
            });

        return ApiResponse::success($types);
    }

    public function visaTypeStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug'       => 'required|string|max:50|regex:/^[a-z_]+$/|unique:portal_visa_types,slug',
            'name_ru'    => 'required|string|max:100',
            'name_uz'    => 'sometimes|nullable|string|max:100',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        DB::table('portal_visa_types')->insert(array_merge($data, [
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return ApiResponse::success(
            DB::table('portal_visa_types')->where('slug', $data['slug'])->first(),
            'Тип визы добавлен',
            201
        );
    }

    public function visaTypeUpdate(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'name_ru'    => 'sometimes|string|max:100',
            'name_uz'    => 'sometimes|nullable|string|max:100',
            'sort_order' => 'sometimes|integer|min:0',
            'is_active'  => 'sometimes|boolean',
        ]);

        $data['updated_at'] = now();
        DB::table('portal_visa_types')->where('slug', $slug)->update($data);

        return ApiResponse::success(
            DB::table('portal_visa_types')->where('slug', $slug)->first()
        );
    }

    public function visaTypeDestroy(string $slug): JsonResponse
    {
        $usageCount = DB::table('country_visa_type_settings')
            ->where('visa_type', $slug)->count();

        if ($usageCount > 0) {
            return ApiResponse::error(
                "Тип визы используется в $usageCount настройках стран. Сначала удалите их.",
                null,
                422
            );
        }

        DB::table('portal_visa_types')->where('slug', $slug)->delete();
        return ApiResponse::success(null, 'Тип визы удалён');
    }

    // =========================================================================
    // Шаблоны документов
    // =========================================================================

    public function documents(Request $request): JsonResponse
    {
        $docs = DocumentTemplate::when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->withCount('countryRequirements')
            ->orderBy('category')->orderBy('name')
            ->get();

        return ApiResponse::success($docs);
    }

    // =========================================================================
    // Управление CRM-пользователями
    // =========================================================================

    public function crmUsers(Request $request): JsonResponse
    {
        $users = User::with('agency:id,name')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%")
                ->orWhere('email', 'ilike', "%{$s}%"))
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->when($request->status === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('created_at')
            ->paginate(30);

        $users->getCollection()->transform(function ($u) {
            $u->agency_name = $u->agency->name ?? null;
            unset($u->agency);
            return $u;
        });

        return ApiResponse::success($users);
    }

    public function crmUserStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:80',
            'email'     => 'required|email|unique:users,email',
            'phone'     => 'nullable|string|max:20',
            'role'      => 'required|in:manager,owner,superadmin,partner',
            'password'  => 'required|string|min:8',
            'agency_id' => 'nullable|uuid|exists:agencies,id',
        ]);

        $data['password']   = Hash::make($data['password']);
        $data['is_active']  = true;

        $user = User::create($data);

        return ApiResponse::success($user->load('agency:id,name'), 'Пользователь создан', 201);
    }

    public function crmUserUpdate(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'is_active' => 'sometimes|boolean',
            'role'      => 'sometimes|in:manager,owner,superadmin,partner',
            'password'  => 'sometimes|string|min:8',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return ApiResponse::success($user->fresh(), 'Пользователь обновлён');
    }

    // =========================================================================
    // Финансы
    // =========================================================================

    public function transactions(Request $request): JsonResponse
    {
        $statusMap = ['paid' => 'succeeded', 'pending' => 'pending', 'failed' => 'failed', 'refund' => 'refunded'];
        $dbStatus  = $request->status ? ($statusMap[$request->status] ?? $request->status) : null;

        $transactions = DB::table('payment_transactions')
            ->leftJoin('agencies', 'payment_transactions.agency_id', '=', 'agencies.id')
            ->select('payment_transactions.*', 'agencies.name as agency_name')
            ->when($dbStatus, fn ($q, $s) => $q->where('payment_transactions.status', $s))
            ->orderByDesc('payment_transactions.created_at')
            ->paginate(30);

        return ApiResponse::success($transactions);
    }
}
