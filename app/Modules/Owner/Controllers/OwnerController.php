<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Document\Models\DocumentTemplate;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OwnerController extends Controller
{
    // =========================================================================
    // Дашборд — глобальные метрики
    // =========================================================================

    public function dashboard(): JsonResponse
    {
        $stats = [
            'public_users' => [
                'total'   => DB::table('public_users')->count(),
                'today'   => DB::table('public_users')
                    ->whereDate('created_at', today())->count(),
                'week'    => DB::table('public_users')
                    ->whereBetween('created_at', [now()->startOfWeek(), now()])->count(),
            ],
            'leads' => [
                'total'      => DB::table('public_leads')->count(),
                'new'        => DB::table('public_leads')->where('status', 'new')->count(),
                'assigned'   => DB::table('public_leads')->where('status', 'assigned')->count(),
                'converted'  => DB::table('public_leads')->where('status', 'converted')->count(),
            ],
            'agencies' => [
                'total'    => Agency::count(),
                'active'   => Agency::where('is_active', true)->count(),
                'verified' => Agency::where('is_verified', true)->count(),
                'trial'    => Agency::where('plan', 'trial')->count(),
            ],
            'revenue' => [
                'total'       => round((DB::table('payment_transactions')
                    ->where('status', 'succeeded')->sum('amount') ?? 0) / 100, 2),
                'commissions' => 0,
                'this_month'  => round((DB::table('payment_transactions')
                    ->where('status', 'succeeded')
                    ->whereBetween('created_at', [now()->startOfMonth(), now()])->sum('amount') ?? 0) / 100, 2),
            ],
            'crm_users' => [
                'total'   => DB::table('cases')->count(),
                'clients' => DB::table('clients')->count(),
            ],
            'top_countries' => DB::table('public_leads')
                ->select('country_code', DB::raw('count(*) as count'))
                ->groupBy('country_code')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'avg_score' => (int) DB::table('public_leads')->avg('score'),
            'recent_agencies' => Agency::orderByDesc('created_at')->limit(5)
                ->get(['id', 'name', 'email', 'plan', 'is_active', 'created_at']),
        ];

        return ApiResponse::success($stats);
    }

    // =========================================================================
    // Управление агентствами
    // =========================================================================

    public function agencies(Request $request): JsonResponse
    {
        $q = Agency::withCount(['users'])
            ->with(['ownerRelation:id,name,email,agency_id'])
            ->when($request->search, fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%")
                ->orWhere('email', 'ilike', "%{$s}%"))
            ->when($request->plan, fn ($q, $p) => $q->where('plan', $p))
            ->when($request->status === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('created_at');

        $agencies = $q->paginate(20);

        // Аггрегация лидов из public_leads
        $agencyIds = $agencies->pluck('id');
        // Note: public_leads хранит assigned_agency_id
        $leadCounts = DB::table('public_leads')
            ->whereIn('assigned_agency_id', $agencyIds)
            ->select('assigned_agency_id', DB::raw('count(*) as leads_count'))
            ->groupBy('assigned_agency_id')
            ->pluck('leads_count', 'assigned_agency_id');

        $agencies->getCollection()->transform(function ($a) use ($leadCounts) {
            $a->leads_count     = $leadCounts[$a->id] ?? 0;
            $a->owner_name      = $a->ownerRelation->name ?? null;
            $a->owner_email     = $a->ownerRelation->email ?? null;
            return $a;
        });

        return ApiResponse::success($agencies);
    }

    public function agencyShow(string $id): JsonResponse
    {
        $agency = Agency::withCount('users')
            ->with(['ownerRelation:id,name,email,agency_id', 'activeSubscription'])
            ->findOrFail($id);

        $agency->leads_count = DB::table('public_leads')
            ->where('assigned_agency_id', $id)->count();
        $agency->cases_count = DB::table('cases')
            ->where('agency_id', $id)->count();
        $agency->clients_count = DB::table('clients')
            ->where('agency_id', $id)->count();

        return ApiResponse::success($agency);
    }

    public function agencyUpdate(Request $request, string $id): JsonResponse
    {
        $agency = Agency::findOrFail($id);

        $data = $request->validate([
            'is_active'       => 'sometimes|boolean',
            'is_verified'     => 'sometimes|boolean',
            'plan'            => 'sometimes|in:trial,starter,pro,enterprise',
            'description'     => 'sometimes|nullable|string|max:1000',
            'plan_expires_at' => 'sometimes|nullable|date',
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
            'block_reason'    => 'sometimes|nullable|string|max:500',
        ]);

        // Если блокируем
        if (isset($data['is_active']) && $data['is_active'] === false && $agency->is_active) {
            $data['blocked_at'] = now();
        }
        if (isset($data['is_active']) && $data['is_active'] === true) {
            $data['blocked_at']   = null;
            $data['block_reason'] = null;
        }

        $agency->update($data);

        return ApiResponse::success($agency->fresh());
    }

    public function agencyDestroy(string $id): JsonResponse
    {
        Agency::findOrFail($id)->delete();
        return ApiResponse::success(null, 'Агентство удалено');
    }

    // =========================================================================
    // Управление пользователями (public portal)
    // =========================================================================

    public function publicUsers(Request $request): JsonResponse
    {
        $users = DB::table('public_users')
            ->when($request->search, fn ($q, $s) => $q->where('phone', 'ilike', "%{$s}%")
                ->orWhere('name', 'ilike', "%{$s}%"))
            ->orderByDesc('created_at')
            ->paginate(30);

        return ApiResponse::success($users);
    }

    public function publicUserShow(string $id): JsonResponse
    {
        $user = DB::table('public_users')->where('id', $id)->first();
        abort_unless($user, 404);

        $leads = DB::table('public_leads')
            ->where('public_user_id', $id)
            ->get();
        $scores = DB::table('public_score_cache')
            ->where('public_user_id', $id)
            ->get();

        return ApiResponse::success([
            'user'   => $user,
            'leads'  => $leads,
            'scores' => $scores,
        ]);
    }

    public function publicUserBlock(string $id): JsonResponse
    {
        DB::table('public_users')->where('id', $id)
            ->update(['api_token' => null, 'updated_at' => now()]);

        return ApiResponse::success(null, 'Пользователь заблокирован (токен сброшен)');
    }

    // =========================================================================
    // Лиды
    // =========================================================================

    public function leads(Request $request): JsonResponse
    {
        $leads = DB::table('public_leads')
            ->leftJoin('public_users',  'public_leads.public_user_id', '=', 'public_users.id')
            ->leftJoin('agencies', 'public_leads.assigned_agency_id', '=', 'agencies.id')
            ->select(
                'public_leads.*',
                'public_users.phone as user_phone',
                'public_users.name  as user_name',
                'agencies.name      as agency_name',
            )
            ->when($request->status, fn ($q, $s) => $q->where('public_leads.status', $s))
            ->when($request->country, fn ($q, $c) => $q->where('public_leads.country_code', $c))
            ->orderByDesc('public_leads.created_at')
            ->paginate(30);

        return ApiResponse::success($leads);
    }

    public function leadUpdate(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status'              => 'sometimes|in:new,assigned,converted,rejected',
            'assigned_agency_id'  => 'sometimes|nullable|uuid',
        ]);

        DB::table('public_leads')->where('id', $id)->update(
            array_merge($data, ['updated_at' => now()])
        );

        return ApiResponse::success(null, 'Лид обновлён');
    }

    // =========================================================================
    // Страны (portal_countries)
    // =========================================================================

    public function countries(): JsonResponse
    {
        $countries = DB::table('portal_countries')->orderBy('sort_order')->get();
        // Декодировать visa_types из JSON-строки в массив
        $countries = $countries->map(function ($c) {
            $c->visa_types = is_string($c->visa_types) ? json_decode($c->visa_types, true) : ($c->visa_types ?? []);
            return $c;
        });
        return ApiResponse::success($countries);
    }

    public function countryUpdate(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'name'                       => 'sometimes|string|max:100',
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
        $types = DB::table('portal_visa_types')->orderBy('sort_order')->get();
        return ApiResponse::success($types);
    }

    public function visaTypeStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug'       => 'required|string|max:50|regex:/^[a-z_]+$/|unique:portal_visa_types,slug',
            'name_ru'    => 'required|string|max:100',
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
        DB::table('portal_visa_types')->where('slug', $slug)->delete();
        return ApiResponse::success(null, 'Тип визы удалён');
    }

    // =========================================================================
    // Шаблоны документов (уже в DocumentTemplateController — дублируем read)
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
        // Маппинг статусов из UI → реальные значения в БД
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
