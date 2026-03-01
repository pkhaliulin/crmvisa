<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Models\Agency;
use App\Modules\Client\Models\Client;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ClientAuthController extends Controller
{
    /**
     * POST /api/v1/client/auth/register
     * Регистрация клиента (через ссылку агентства или маркетплейс)
     *
     * Обязателен agency_slug — клиент привязывается к конкретному агентству.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agency_slug' => ['required', 'string', 'exists:agencies,slug'],
            'name'        => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $agency = Agency::where('slug', $validated['agency_slug'])->where('is_active', true)->firstOrFail();

        $result = DB::transaction(function () use ($validated, $agency) {
            $user = User::create([
                'agency_id' => $agency->id,
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'phone'     => $validated['phone'] ?? null,
                'password'  => Hash::make($validated['password']),
                'role'      => 'client',
                'is_active' => true,
            ]);

            // Создаём запись клиента, связанную с этим пользователем
            $client = Client::firstOrCreate(
                ['agency_id' => $agency->id, 'email' => $validated['email']],
                [
                    'name'   => $validated['name'],
                    'phone'  => $validated['phone'] ?? null,
                    'source' => 'direct',
                ]
            );

            // Связываем клиента с пользователем
            $client->update(['user_id' => $user->id]);

            $token = JWTAuth::fromUser($user);

            return compact('user', 'client', 'token');
        });

        return ApiResponse::created([
            'token'  => $result['token'],
            'client' => [
                'id'    => $result['client']->id,
                'name'  => $result['user']->name,
                'email' => $result['user']->email,
            ],
        ], 'Registration successful');
    }

    /**
     * POST /api/v1/client/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = JWTAuth::attempt(['email' => $validated['email'], 'password' => $validated['password'], 'role' => 'client']);

        if (! $token) {
            return ApiResponse::unauthorized('Invalid credentials');
        }

        $user   = JWTAuth::user();
        $client = Client::where('user_id', $user->id)->first();

        return ApiResponse::success([
            'token'  => $token,
            'client' => [
                'id'    => $client?->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 'Login successful');
    }

    /**
     * POST /api/v1/client/auth/logout
     */
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return ApiResponse::success(null, 'Logged out');
    }
}
