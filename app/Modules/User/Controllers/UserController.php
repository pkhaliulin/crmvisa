<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::where('agency_id', $request->user()->agency_id)
            ->get(['id', 'name', 'email', 'phone', 'role', 'is_active', 'created_at']);

        return ApiResponse::success($users);
    }

    public function store(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        // Проверка лимита менеджеров по плану
        $agency      = Agency::findOrFail($agencyId);
        $activeCount = User::where('agency_id', $agencyId)->where('is_active', true)->count();

        if ($activeCount >= $agency->plan->maxManagers()) {
            return ApiResponse::error(
                "Manager limit reached for your plan ({$agency->plan->maxManagers()} max). Upgrade to add more.",
                null,
                422
            );
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', Rule::unique('users', 'email')],
            'phone'    => ['nullable', 'string', 'max:30'],
            'role'     => ['required', 'in:manager,partner'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'agency_id' => $agencyId,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'role'      => $data['role'],
            'password'  => Hash::make($data['password']),
            'is_active' => true,
        ]);

        return ApiResponse::created($user->only(['id', 'name', 'email', 'role', 'agency_id']));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::where('id', $id)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:255'],
            'phone'     => ['sometimes', 'nullable', 'string', 'max:30'],
            'role'      => ['sometimes', 'in:manager,partner'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user->update($data);

        return ApiResponse::success($user->only(['id', 'name', 'email', 'role', 'is_active']));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::where('id', $id)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();

        if ($user->id === $request->user()->id) {
            return ApiResponse::error('Cannot delete yourself.', null, 422);
        }

        if ($user->role === 'owner') {
            return ApiResponse::error('Cannot delete the agency owner.', null, 422);
        }

        $user->update(['is_active' => false]);
        $user->delete();

        return ApiResponse::success(null, 'User deleted.');
    }
}
