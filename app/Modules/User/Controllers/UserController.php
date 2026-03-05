<?php

namespace App\Modules\User\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::where('agency_id', $request->user()->agency_id)
            ->get(['id', 'name', 'email', 'phone', 'telegram_username', 'avatar_url', 'role', 'is_active', 'created_at']);

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
            'first_name'        => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s\-\']+$/'],
            'last_name'         => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s\-\']+$/'],
            'email'             => ['required', 'email', Rule::unique('users', 'email')],
            'phone'             => ['nullable', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'telegram_username' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_]{3,32}$/'],
            'avatar'            => ['nullable', 'image', 'max:2048'],
            'role'              => ['required', 'in:manager,partner'],
            'password'          => ['required', 'string', 'min:8'],
        ]);

        $fullName = trim($data['first_name']) . ' ' . trim($data['last_name']);

        $avatarUrl = null;
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $avatarUrl = '/storage/' . $path;
        }

        $user = User::create([
            'agency_id'         => $agencyId,
            'name'              => $fullName,
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'telegram_username' => $data['telegram_username'] ?? null,
            'avatar_url'        => $avatarUrl,
            'role'              => $data['role'],
            'password'          => Hash::make($data['password']),
            'is_active'         => true,
        ]);

        // Отправляем email с данными для входа
        $this->sendWelcomeEmail($user, $data['password'], $agency->name);

        return ApiResponse::created($user->only(['id', 'name', 'email', 'role', 'agency_id', 'avatar_url']));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::where('id', $id)
            ->where('agency_id', $request->user()->agency_id)
            ->firstOrFail();

        $data = $request->validate([
            'name'              => ['sometimes', 'string', 'max:255'],
            'phone'             => ['sometimes', 'nullable', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'telegram_username' => ['sometimes', 'nullable', 'string', 'regex:/^[a-zA-Z0-9_]{3,32}$/'],
            'role'              => ['sometimes', 'in:manager,partner'],
            'is_active'         => ['sometimes', 'boolean'],
        ]);

        if ($request->hasFile('avatar')) {
            $request->validate(['avatar' => ['image', 'max:2048']]);
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = '/storage/' . $path;
        }

        $user->update($data);

        return ApiResponse::success($user->only(['id', 'name', 'email', 'role', 'is_active', 'avatar_url']));
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

    private function sendWelcomeEmail(User $user, string $password, string $agencyName): void
    {
        try {
            Mail::raw(
                "Здравствуйте, {$user->name}!\n\n" .
                "Вам создан аккаунт в системе VisaBor для агентства \"{$agencyName}\".\n\n" .
                "Данные для входа:\n" .
                "Ссылка: https://visabor.uz/login\n" .
                "Логин (email): {$user->email}\n" .
                "Пароль: {$password}\n\n" .
                "Рекомендуем сменить пароль после первого входа.\n\n" .
                "С уважением,\nКоманда VisaBor",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('VisaBor — Данные для входа в систему');
                }
            );
        } catch (\Throwable $e) {
            // Не блокируем создание пользователя если email не отправился
            \Log::warning('Failed to send welcome email', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
