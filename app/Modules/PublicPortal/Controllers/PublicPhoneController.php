<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\PhoneChangedNotification;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicPhoneController extends Controller
{
    /**
     * POST /public/me/change-phone/send-otp
     * Отправить OTP на новый номер телефона.
     */
    public function changePhoneSendOtp(Request $request): JsonResponse
    {
        $request->validate(['phone' => ['required', 'string', 'max:20']]);

        $phone = \App\Support\Traits\NormalizesPhone::normalizePhone($request->phone);
        $currentUser = $request->get('_public_user');

        if ($phone === $currentUser->phone) {
            return ApiResponse::error('Это ваш текущий номер.', null, 422);
        }

        // Проверяем что номер не занят другим пользователем
        $existing = \App\Modules\PublicPortal\Models\PublicUser::where('phone', $phone)->first();
        if ($existing && $existing->id !== $currentUser->id) {
            return ApiResponse::error('Этот номер уже зарегистрирован.', null, 422);
        }

        app(\App\Modules\PublicPortal\Services\PhoneAuthService::class)->sendOtp($phone);

        return ApiResponse::success(null, 'Код отправлен на новый номер.');
    }

    /**
     * POST /public/me/change-phone/verify
     * Подтвердить смену номера по OTP.
     */
    public function changePhoneVerify(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code'  => ['required', 'string', 'size:4'],
        ]);

        $phone = \App\Support\Traits\NormalizesPhone::normalizePhone($request->phone);
        $user  = $request->get('_public_user');

        // Проверяем OTP
        $stubPin = config('services.sms_stub.pin');
        $otp = \DB::table('public_otp_codes')
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->when($stubPin, fn ($q) => $q->where('code', $stubPin),
                            fn ($q) => $q->where('code', $request->code))
            ->first();

        if (! $otp) {
            return ApiResponse::error('Неверный или истёкший код.', null, 422);
        }

        \DB::table('public_otp_codes')->where('id', $otp->id)->update(['used_at' => now()]);

        $oldPhone = $user->phone;
        $user->update(['phone' => $phone]);

        // Уведомляем по email о смене телефона (если email подтверждён)
        if ($user->recovery_email && $user->email_verified_at) {
            try {
                Mail::to($user->recovery_email)->send(new PhoneChangedNotification(
                    userName: $user->name ?? 'Пользователь',
                    oldPhone: $oldPhone,
                    newPhone: $phone,
                ));
            } catch (\Throwable $e) {
                \Log::warning('Phone changed notification failed', ['error' => $e->getMessage()]);
            }
        }

        return ApiResponse::success([
            'user' => $user->fresh(),
        ], 'Номер телефона изменён.');
    }
}
