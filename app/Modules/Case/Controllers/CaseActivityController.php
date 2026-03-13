<?php

namespace App\Modules\Case\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Case\Models\CaseActivity;
use App\Modules\Case\Models\VisaCase;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseActivityController extends Controller
{
    /**
     * GET /cases/{id}/activities — CRM view (все активности включая internal notes).
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $case = VisaCase::where('id', $id)
            ->where('agency_id', $user->agency_id)
            ->firstOrFail();

        $this->authorize('view', $case);

        $activities = CaseActivity::where('case_id', $id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(50);

        $items = $activities->map(fn ($a) => [
            'id'          => $a->id,
            'type'        => $a->type,
            'description' => $a->description,
            'metadata'    => $a->metadata,
            'is_internal' => $a->is_internal,
            'user'        => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name] : null,
            'created_at'  => $a->created_at->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $activities->currentPage(),
                'last_page'    => $activities->lastPage(),
                'total'        => $activities->total(),
            ],
        ]);
    }

    /**
     * GET /public/me/cases/{id}/activities — Client view (без internal notes).
     */
    public function publicIndex(Request $request, string $id): JsonResponse
    {
        $publicUser = $request->get('_public_user');

        $case = VisaCase::where('id', $id)
            ->whereHas('client', function ($q) use ($publicUser) {
                $q->where('public_user_id', $publicUser->id);
            })
            ->firstOrFail();

        $activities = CaseActivity::where('case_id', $id)
            ->where('is_internal', false)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(50);

        $items = $activities->map(fn ($a) => [
            'id'          => $a->id,
            'type'        => $a->type,
            'description' => $a->description,
            'metadata'    => $a->metadata,
            'user'        => $a->user ? ['name' => $a->user->name] : null,
            'created_at'  => $a->created_at->toISOString(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }
}
