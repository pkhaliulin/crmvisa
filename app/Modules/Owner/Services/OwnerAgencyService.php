<?php

namespace App\Modules\Owner\Services;

use App\Modules\Agency\Models\Agency;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerAgencyService
{
    public function listAgencies(Request $request): LengthAwarePaginator
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

        $agencyIds = $agencies->pluck('id');
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

        return $agencies;
    }

    public function showAgency(string $id): Agency
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

        return $agency;
    }

    public function getPublicUserDetails(string $id): array
    {
        $user = \App\Modules\PublicPortal\Models\PublicUser::find($id);
        abort_unless($user, 404);

        $leads = DB::table('public_leads')
            ->where('public_user_id', $id)
            ->get();
        $scores = DB::table('public_score_cache')
            ->where('public_user_id', $id)
            ->get();

        return [
            'user'   => $user,
            'leads'  => $leads,
            'scores' => $scores,
        ];
    }
}
