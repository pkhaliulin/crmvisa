<?php

namespace App\Modules\PublicPortal\Services;

use App\Modules\Group\Services\GroupService;
use App\Modules\PublicPortal\Models\PublicUser;
use App\Support\Traits\NormalizesPhone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class PhoneAuthService
{
    /** Максимум неудачных попыток верификации OTP */
    private const MAX_VERIFY_ATTEMPTS = 5;

    /** Время блокировки и окно подсчёта попыток (секунды) */
    private const BLOCK_DURATION = 900; // 15 минут

    /** Минимальный интервал между отправками OTP (секунды) */
    private const OTP_COOLDOWN = 60;

    public function __construct(private SmsService $sms) {}

    // -------------------------------------------------------------------------
    // Шаг 1: Отправить OTP на телефон
    // -------------------------------------------------------------------------

    public function sendOtp(string $phone): bool
    {
        $phone = NormalizesPhone::normalizePhone($phone);

        // Cooldown: нельзя запрашивать OTP чаще чем раз в 60 секунд
        $lastOtp = DB::table('public_otp_codes')
            ->where('phone', $phone)
            ->orderByDesc('created_at')
            ->first();

        if ($lastOtp && now()->diffInSeconds($lastOtp->created_at, false) > -self::OTP_COOLDOWN) {
            Log::channel('auth')->warning('OTP cooldown active', [
                'phone' => $this->maskPhone($phone),
            ]);
            throw new \InvalidArgumentException('Подождите перед повторным запросом кода.');
        }

        // SMS stub работает ТОЛЬКО в local/testing
        $stubPin = $this->getStubPin();
        $code    = $stubPin ?? str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        // Инвалидируем все неиспользованные OTP для этого номера
        DB::table('public_otp_codes')
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        DB::table('public_otp_codes')->insert([
            'id'         => (string) Str::uuid(),
            'phone'      => $phone,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::channel('auth')->info('OTP sent', [
            'phone' => $this->maskPhone($phone),
        ]);

        if ($stubPin) {
            return true; // заглушка -- SMS не отправляется
        }

        return $this->sms->sendOtp($phone, $code, 'visabor');
    }

    // -------------------------------------------------------------------------
    // Шаг 2: Проверить OTP -> вернуть пользователя + токен
    // -------------------------------------------------------------------------

    public function verifyOtp(string $phone, string $code): array
    {
        $phone = NormalizesPhone::normalizePhone($phone);

        // Anti-bruteforce: проверяем блокировку
        $this->checkBruteforceBlock($phone);

        $stubPin = $this->getStubPin();

        $otp = DB::table('public_otp_codes')
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->when($stubPin, fn ($q) => $q->where('code', $stubPin),
                            fn ($q) => $q->where('code', $code))
            ->first();

        if (! $otp) {
            $this->recordFailedAttempt($phone);

            Log::channel('auth')->warning('OTP verification failed', [
                'phone'  => $this->maskPhone($phone),
                'reason' => 'invalid_or_expired_code',
            ]);

            throw new \InvalidArgumentException('Неверный или истёкший код подтверждения.');
        }

        // Успешная верификация -- сбрасываем счётчик неудачных попыток
        $this->resetFailedAttempts($phone);

        // Помечаем OTP использованным
        DB::table('public_otp_codes')->where('id', $otp->id)->update(['used_at' => now()]);

        // Создаём или находим пользователя
        $user = PublicUser::firstOrCreate(['phone' => $phone]);

        $plainToken = $this->rotateToken($user);

        $user->update(['last_login_at' => now()]);

        app(GroupService::class)->linkPendingInvitations($user);

        Log::channel('auth')->info('OTP verified successfully', [
            'phone' => $this->maskPhone($phone),
        ]);

        return ['user' => $user->fresh(), 'token' => $plainToken];
    }

    // -------------------------------------------------------------------------
    // Вход по PIN (повторный)
    // -------------------------------------------------------------------------

    public function loginWithPin(string $phone, string $pin): array
    {
        $phone   = NormalizesPhone::normalizePhone($phone);
        $stubPin = $this->getStubPin();
        $user    = PublicUser::where('phone', $phone)->first();

        $pinOk = $stubPin
            ? ($pin === $stubPin)
            : ($user && $user->verifyPin($pin));

        if (! $user || ! $pinOk) {
            throw new \InvalidArgumentException('Неверный телефон или PIN-код.');
        }

        $plainToken = $this->rotateToken($user);
        $user->update(['last_login_at' => now()]);

        \App\Support\Helpers\AuditLog::log('auth.login', [
            'public_user_id' => $user->id,
            'phone' => $phone,
        ]);

        app(GroupService::class)->linkPendingInvitations($user);

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

    /**
     * Stub PIN — если задан в .env, используется как фиксированный OTP-код.
     * SMS не отправляется. Работает в любом окружении.
     */
    private function getStubPin(): ?string
    {
        return config('services.sms_stub.pin');
    }

    /**
     * Маскировать телефон для логов: +998901234567 -> +998***4567
     */
    private function maskPhone(string $phone): string
    {
        $len = mb_strlen($phone);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return mb_substr($phone, 0, 4) . str_repeat('*', $len - 8) . mb_substr($phone, -4);
    }

    /**
     * Проверить, не заблокирован ли телефон из-за bruteforce.
     */
    private function checkBruteforceBlock(string $phone): void
    {
        $key      = "otp_fails:{$phone}";
        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= self::MAX_VERIFY_ATTEMPTS) {
            Log::channel('auth')->warning('Phone blocked due to too many failed OTP attempts', [
                'phone'    => $this->maskPhone($phone),
                'attempts' => $attempts,
            ]);

            throw new TooManyRequestsHttpException(
                self::BLOCK_DURATION,
                'Слишком много неудачных попыток. Повторите через 15 минут.'
            );
        }
    }

    /**
     * Записать неудачную попытку верификации OTP.
     */
    private function recordFailedAttempt(string $phone): void
    {
        $key      = "otp_fails:{$phone}";
        $attempts = (int) Cache::get($key, 0);

        Cache::put($key, $attempts + 1, self::BLOCK_DURATION);

        if ($attempts + 1 >= self::MAX_VERIFY_ATTEMPTS) {
            Log::channel('auth')->warning('Phone blocked after max failed OTP attempts', [
                'phone'    => $this->maskPhone($phone),
                'attempts' => $attempts + 1,
            ]);
        }
    }

    /**
     * Сбросить счётчик неудачных попыток после успешной верификации.
     */
    private function resetFailedAttempts(string $phone): void
    {
        Cache::forget("otp_fails:{$phone}");
    }
}
