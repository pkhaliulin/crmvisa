<?php

namespace App\Modules\Owner\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerLeadService
{
    public function listLeads(Request $request): LengthAwarePaginator
    {
        return DB::table('public_leads')
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
    }

    public function updateLead(string $id, array $data): void
    {
        DB::table('public_leads')->where('id', $id)->update(
            array_merge($data, ['updated_at' => now()])
        );
    }
}
