<?php

namespace App\Modules\Owner\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Owner\Services\OwnerAgencyService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerAgencyController extends Controller
{
    public function __construct(
        private readonly OwnerAgencyService $agencyService,
    ) {}

    public function agencies(Request $request): JsonResponse
    {
        return ApiResponse::success($this->agencyService->listAgencies($request));
    }

    public function agencyShow(string $id): JsonResponse
    {
        return ApiResponse::success($this->agencyService->showAgency($id));
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

        \App\Support\Helpers\AuditLog::log('agency.updated', [
            'agency_id' => $agency->id,
            'agency_name' => $agency->name,
            'changes' => $data,
            'admin' => auth()->user()?->email,
        ]);

        return ApiResponse::success($agency->fresh());
    }

    public function agencyDestroy(string $id): JsonResponse
    {
        $agency = Agency::findOrFail($id);

        \App\Support\Helpers\AuditLog::log('agency.deleted', [
            'agency_id' => $agency->id,
            'agency_name' => $agency->name,
            'admin' => auth()->user()?->email,
        ]);

        $agency->delete();
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
        return ApiResponse::success($this->agencyService->getPublicUserDetails($id));
    }

    public function publicUserBlock(string $id): JsonResponse
    {
        DB::table('public_users')->where('id', $id)
            ->update(['api_token' => null, 'updated_at' => now()]);

        return ApiResponse::success(null, 'Пользователь заблокирован (токен сброшен)');
    }
}
