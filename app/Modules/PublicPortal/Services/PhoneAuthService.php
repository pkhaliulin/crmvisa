<?php

namespace App\Modules\PublicPortal\Services;

use App\Modules\PublicPortal\Models\PublicUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PhoneAuthService
{
    public function __construct(private SmsService $sms) {}

    // -------------------------------------------------------------------------
    // Шаг 1: Отправить OTP на телефон
    // -------------------------------------------------------------------------

    public function sendOtp(string $phone): bool
    {
        $stubPin = config('services.sms_stub.pin');
        $code    = $stubPin ?? str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        // Удаляем старые OTP для этого номера
        DB::table('public_otp_codes')->where('phone', $phone)->delete();

        DB::table('public_otp_codes')->insert([
            'id'         => (string) Str::uuid(),
            'phone'      => $phone,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($stubPin) {
            return true; // заглушка — SMS не отправляется
        }

        $message = "Ваш код VisaBor: {$code}. Действует 10 минут.";
        return $this->sms->send($phone, $message);
    }

    // -------------------------------------------------------------------------
    // Шаг 2: Проверить OTP → вернуть пользователя + токен
    // -------------------------------------------------------------------------

    public function verifyOtp(string $phone, string $code): array
    {
        $stubPin = config('services.sms_stub.pin');

        $otp = DB::table('public_otp_codes')
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->when($stubPin, fn ($q) => $q->where('code', $stubPin),
                            fn ($q) => $q->where('code', $code))
            ->first();

        if (! $otp) {
            throw new \InvalidArgumentException('Неверный или истёкший код подтверждения.');
        }

        // Помечаем OTP использованным
        DB::table('public_otp_codes')->where('id', $otp->id)->update(['used_at' => now()]);

        // Создаём или находим пользователя
        $user = PublicUser::firstOrCreate(['phone' => $phone]);

        $plainToken = $this->rotateToken($user);

        $user->update(['last_login_at' => now()]);

        return ['user' => $user->fresh(), 'token' => $plainToken];
    }

    // -------------------------------------------------------------------------
    // Вход по PIN (повторный)
    // -------------------------------------------------------------------------

    public function loginWithPin(string $phone, string $pin): array
    {
        $stubPin = config('services.sms_stub.pin');
        $user    = PublicUser::where('phone', $phone)->first();

        $pinOk = $stubPin
            ? ($pin === $stubPin)
            : ($user && $user->verifyPin($pin));

        if (! $user || ! $pinOk) {
            throw new \InvalidArgumentException('Неверный телефон или PIN-код.');
        }

        $plainToken = $this->rotateToken($user);
        $user->update(['last_login_at' => now()]);

        return ['user' => $user->fresh(), 'token' => $plainToken];
    }

    // -------------------------------------------------------------------------
    // Найти пользователя по Bearer-токену
    // -------------------------------------------------------------------------

    public function findByToken(string $plainToken): ?PublicUser
    {
        $hash = hash('sha256', $plainToken);
        return PublicUser::where('api_token', $hash)->first();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function rotateToken(PublicUser $user): string
    {
        $plain = Str::random(64);
        $user->update(['api_token' => hash('sha256', $plain)]);
        return $plain;
    }
}
