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
                'total'       => DB::table('billing_transactions')
                    ->where('type', 'payment')->where('status', 'paid')->sum('amount') ?? 0,
                'commissions' => DB::table('billing_transactions')
                    ->where('type', 'commission')->where('status', 'paid')->sum('amount') ?? 0,
                'this_month'  => DB::table('billing_transactions')
                    ->where('type', 'payment')->where('status', 'paid')
                    ->whereBetween('created_at', [now()->startOfMonth(), now()])->sum('amount') ?? 0,
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
            'plan'            => 'sometimes|in:trial,starter,professional,premium',
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
        return ApiResponse::success($countries);
    }

    public function countryUpdate(Request $request, string $code): JsonResponse
    {
        $data = $request->validate([
            'name'                    => 'sometimes|string|max:100',
            'flag_emoji'              => 'sometimes|string|max:10',
            'is_active'               => 'sometimes|boolean',
            'weight_finance'          => 'sometimes|numeric|min:0|max:1',
            'weight_ties'             => 'sometimes|numeric|min:0|max:1',
            'weight_travel'           => 'sometimes|numeric|min:0|max:1',
            'weight_profile'          => 'sometimes|numeric|min:0|max:1',
            'min_monthly_income_usd'  => 'sometimes|integer|min:0',
            'min_score'               => 'sometimes|integer|min:0|max:100',
            'sort_order'              => 'sometimes|integer|min:0',
        ]);

        $data['updated_at'] = now();
        DB::table('portal_countries')->where('country_code', $code)->update($data);

        // Сбросить кеш весов
        Cache::forget('portal_countries_weights');
        Cache::forget('portal_countries_codes');

        return ApiResponse::success(
            DB::table('portal_countries')->where('country_code', $code)->first()
        );
    }

    public function countryStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_code'            => 'required|string|size:2|uppercase',
            'name'                    => 'required|string|max:100',
            'flag_emoji'              => 'required|string|max:10',
            'is_active'               => 'sometimes|boolean',
            'weight_finance'          => 'required|numeric|min:0|max:1',
            'weight_ties'             => 'required|numeric|min:0|max:1',
            'weight_travel'           => 'required|numeric|min:0|max:1',
            'weight_profile'          => 'required|numeric|min:0|max:1',
            'min_monthly_income_usd'  => 'sometimes|integer|min:0',
            'min_score'               => 'sometimes|integer|min:0|max:100',
        ]);

        abort_if(DB::table('portal_countries')->where('country_code', $data['country_code'])->exists(), 422,
            'Страна уже существует');

        DB::table('portal_countries')->insert(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        Cache::forget('portal_countries_weights');
        Cache::forget('portal_countries_codes');

        return ApiResponse::success(
            DB::table('portal_countries')->where('country_code', $data['country_code'])->first(),
            'Страна добавлена',
            201
        );
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
            ->orderByDesc('created_at')
            ->paginate(30);

        return ApiResponse::success($users);
    }

    // =========================================================================
    // Финансы
    // =========================================================================

    public function transactions(Request $request): JsonResponse
    {
        $transactions = DB::table('billing_transactions')
            ->leftJoin('agencies', 'billing_transactions.agency_id', '=', 'agencies.id')
            ->select('billing_transactions.*', 'agencies.name as agency_name')
            ->when($request->type, fn ($q, $t) => $q->where('billing_transactions.type', $t))
            ->when($request->status, fn ($q, $s) => $q->where('billing_transactions.status', $s))
            ->orderByDesc('billing_transactions.created_at')
            ->paginate(30);

        return ApiResponse::success($transactions);
    }
}
