<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Services\AuthService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agency_name' => ['required', 'string', 'max:255'],
            'owner_name'  => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            'phone'       => ['nullable', 'string', 'max:30'],
            'country'     => ['nullable', 'string', 'size:2'],
            'timezone'    => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $result = $this->authService->register(RegisterDTO::fromArray($data));

            return ApiResponse::created($result, 'Agency registered successfully.');
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        }
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $this->authService->login(LoginDTO::fromArray($data));

            return ApiResponse::success($result, 'Login successful.');
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Authentication failed.');
        }
    }

    public function verifyEmail(Request $request, string $id): \Illuminate\Http\Response
    {
        if (! $request->hasValidSignature()) {
            return response('<h1>Ссылка недействительна или истекла</h1><p>Запросите новое письмо для подтверждения.</p>', 403);
        }

        $user = \App\Modules\User\Models\User::find($id);

        if (! $user) {
            return response('<h1>Пользователь не найден</h1>', 404);
        }

        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        return response(view('emails.verify-success'));
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return ApiResponse::success(null, 'Email уже подтверждён');
        }

        $this->authService->sendVerificationEmail($user);

        return ApiResponse::success(null, 'Письмо отправлено');
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();

            return ApiResponse::success($result, 'Token refreshed.');
        } catch (\Exception $e) {
            return ApiResponse::unauthorized('Token refresh failed.');
        }
    }

    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return ApiResponse::success([
            'id'        => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'role'      => $user->role,
            'agency_id' => $user->agency_id,
            'agency'    => $user->agency ? [
                'id'   => $user->agency->id,
                'name' => $user->agency->name,
                'plan' => $user->agency->plan,
            ] : null,
        ]);
    }
}
