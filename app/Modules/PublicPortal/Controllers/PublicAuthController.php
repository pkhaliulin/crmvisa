<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicPortal\Services\PhoneAuthService;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAuthController extends Controller
{
    public function __construct(private PhoneAuthService $auth) {}

    /**
     * POST /public/auth/send-otp
     * Отправить OTP-код на телефон.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:20',
        ]);

        $sent = $this->auth->sendOtp($request->phone);

        if (! $sent) {
            return ApiResponse::error('Не удалось отправить SMS. Попробуйте ещё раз.', 503);
        }

        return ApiResponse::success(null, 'SMS с кодом подтверждения отправлен');
    }

    /**
     * POST /public/auth/verify-otp
     * Проверить OTP, получить токен.
     * Первый вход — токен в ответе, PIN устанавливается позже.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string|digits:6',
        ]);

        try {
            ['user' => $user, 'token' => $token] = $this->auth->verifyOtp(
                $request->phone,
                $request->code
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success([
            'user'       => $user,
            'token'      => $token,
            'is_new'     => ! $user->pin_hash,
            'profile_ok' => $user->name && $user->dob && $user->citizenship,
        ], 'Авторизация успешна');
    }

    /**
     * POST /public/auth/set-pin
     * Установить PIN-код после первого входа.
     */
    public function setPin(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|digits:4',
        ]);

        $user = $request->get('_public_user');
        $user->setPin($request->pin);

        return ApiResponse::success(null, 'PIN-код установлен');
    }

    /**
     * POST /public/auth/login
     * Вход по телефону + PIN (повторный).
     */
    public function loginWithPin(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'pin'   => 'required|digits:4',
        ]);

        try {
            ['user' => $user, 'token' => $token] = $this->auth->loginWithPin(
                $request->phone,
                $request->pin
            );
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success([
            'user'  => $user,
            'token' => $token,
        ], 'Вход выполнен');
    }
}
