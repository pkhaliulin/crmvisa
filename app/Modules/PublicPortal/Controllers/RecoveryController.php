<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\RecoveryLink;
use App\Modules\PublicPortal\Models\PublicUser;
use App\Support\Helpers\ApiResponse;
use App\Support\Traits\NormalizesPhone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RecoveryController extends Controller
{
    /**
     * POST /public/recovery/request
     * Запросить восстановление — отправляем ссылку на verified email.
     */
    public function request(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $phone = NormalizesPhone::normalizePhone($request->phone);
        $user  = PublicUser::where('phone', $phone)->first();

        if (! $user || ! $user->recovery_email || ! $user->email_verified_at) {
            // Не раскрываем существование аккаунта
            return ApiResponse::success(null, 'Если аккаунт с этим номером существует и email подтверждён — ссылка для восстановления отправлена.');
        }

        // Удаляем старые неиспользованные токены
        DB::table('public_recovery_tokens')
            ->where('public_user_id', $user->id)
            ->whereNull('used_at')
            ->delete();

        $token = Str::random(64);

        DB::table('public_recovery_tokens')->insert([
            'id'              => Str::uuid()->toString(),
            'public_user_id'  => $user->id,
            'token'           => $token,
            'expires_at'      => now()->addMinutes(30),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        try {
            Mail::to($user->recovery_email)->send(new RecoveryLink(
                userName: $user->name ?? 'Пользователь',
                token: $token,
            ));
        } catch (\Throwable $e) {
            \Log::warning('Recovery link send failed', ['error' => $e->getMessage()]);
        }

        return ApiResponse::success(null, 'Если аккаунт с этим номером существует и email подтверждён — ссылка для восстановления отправлена.');
    }

    /**
     * POST /public/recovery/verify-token
     * Проверить токен восстановления (валиден ли).
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string|size:64']);

        $record = DB::table('public_recovery_tokens')
            ->where('token', $request->token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return ApiResponse::error('Ссылка недействительна или истекла.', null, 422);
        }

        $user = PublicUser::find($record->public_user_id);

        return ApiResponse::success([
            'valid'      => true,
            'user_name'  => $user?->name,
            'masked_phone' => $user ? $this->maskPhone($user->phone) : null,
        ]);
    }

    /**
     * POST /public/recovery/change-phone
     * Привязать новый телефон через recovery-токен.
     * Шаг 1: отправить OTP на новый номер.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'phone' => 'required|string|max:20',
        ]);

        $record = DB::table('public_recovery_tokens')
            ->where('token', $request->token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return ApiResponse::error('Ссылка недействительна или истекла.', null, 422);
        }

        $phone = NormalizesPhone::normalizePhone($request->phone);

        // Проверяем что номер не занят
        $existing = PublicUser::where('phone', $phone)->first();
        if ($existing && $existing->id !== $record->public_user_id) {
            return ApiResponse::error('Этот номер уже зарегистрирован.', null, 422);
        }

        app(\App\Modules\PublicPortal\Services\PhoneAuthService::class)->sendOtp($phone);

        return ApiResponse::success(null, 'Код отправлен на новый номер.');
    }

    /**
     * POST /public/recovery/confirm
     * Подтвердить новый телефон через OTP + recovery-токен.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'phone' => 'required|string|max:20',
            'code'  => 'required|string|size:4',
        ]);

        $record = DB::table('public_recovery_tokens')
            ->where('token', $request->token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return ApiResponse::error('Ссылка недействительна или истекла.', null, 422);
        }

        $phone   = NormalizesPhone::normalizePhone($request->phone);
        $stubPin = config('services.sms_stub.pin');

        $otp = DB::table('public_otp_codes')
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->when($stubPin, fn ($q) => $q->where('code', $stubPin),
                            fn ($q) => $q->where('code', $request->code))
            ->first();

        if (! $otp) {
            return ApiResponse::error('Неверный или истёкший код.', null, 422);
        }

        DB::table('public_otp_codes')->where('id', $otp->id)->update(['used_at' => now()]);
        DB::table('public_recovery_tokens')->where('id', $record->id)->update(['used_at' => now()]);

        $user = PublicUser::find($record->public_user_id);
        $oldPhone = $user->phone;
        $user->update(['phone' => $phone]);

        // Уведомляем на email о смене телефона
        if ($user->recovery_email && $user->email_verified_at) {
            try {
                Mail::to($user->recovery_email)->send(new \App\Mail\PhoneChangedNotification(
                    userName: $user->name ?? 'Пользователь',
                    oldPhone: $oldPhone,
                    newPhone: $phone,
                ));
            } catch (\Throwable $e) {
                \Log::warning('Phone changed notification failed', ['error' => $e->getMessage()]);
            }
        }

        // Генерируем новый токен авторизации
        $apiToken = hash('sha256', Str::random(60));
        $user->update(['api_token' => $apiToken]);

        return ApiResponse::success([
            'token' => $apiToken,
            'user'  => $user->fresh(),
        ], 'Номер телефона восстановлен. Вы вошли в аккаунт.');
    }

    private function maskPhone(string $phone): string
    {
        // +998901234567 → +998 *** ** 67
        if (strlen($phone) >= 6) {
            return substr($phone, 0, 4) . ' *** ** ' . substr($phone, -2);
        }
        return '*** ***';
    }
}
