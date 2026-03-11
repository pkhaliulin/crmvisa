<?php

namespace App\Modules\PublicPortal\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const TOKEN_CACHE_KEY = 'eskiz_token';
    private const BASE_URL        = 'https://notify.eskiz.uz/api';

    /** Шаблоны OTP по контексту (латиница, до 160 символов = 1 SMS) */
    private const OTP_TEMPLATES = [
        'visabor' => "Sizning raqamingizdan VisaBor platformasiga kirish so'raldi. Tasdiqlash kodi: %s. Kodni hech kimga bermang. Kod 10 daqiqa amal qiladi.",
        'visacrm' => "Sizning raqamingizdan VisaCRM tizimiga kirish so'raldi. Tasdiqlash kodi: %s. Kodni hech kimga bermang. Kod 10 daqiqa amal qiladi.",
        'default' => "Sizning raqamingizdan tizimga kirish so'raldi. Tasdiqlash kodi: %s. Ushbu kodni hech kimga bermang. Kod 10 daqiqa amal qiladi.",
    ];

    /**
     * Отправить OTP-код с брендированным шаблоном.
     *
     * @param string $context  visabor|visacrm|default
     */
    public function sendOtp(string $phone, string $code, string $context = 'visabor'): bool
    {
        $template = self::OTP_TEMPLATES[$context] ?? self::OTP_TEMPLATES['default'];
        $message  = sprintf($template, $code);

        return $this->send($phone, $message);
    }

    /**
     * Отправить произвольную SMS через Eskiz.uz.
     * В dev-режиме только логирует, не отправляет реально.
     */
    public function send(string $phone, string $message): bool
    {
        if (app()->isLocal() || app()->environment('testing') || config('services.sms_stub.pin')) {
            Log::info("[SMS STUB] To: {$phone} | Message: {$message}");
            return true;
        }

        try {
            $token = $this->getToken();

            $response = Http::withToken($token)
                ->post(self::BASE_URL . '/message/sms/send', [
                    'mobile_phone' => $this->normalizePhone($phone),
                    'message'      => $message,
                    'from'         => config('services.eskiz.from'),
                    'callback_url' => null,
                ]);

            if ($response->failed()) {
                Log::channel('auth')->warning('[SMS] First attempt failed, refreshing token', [
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 200),
                ]);

                Cache::forget(self::TOKEN_CACHE_KEY);
                $token    = $this->getToken();
                $response = Http::withToken($token)
                    ->post(self::BASE_URL . '/message/sms/send', [
                        'mobile_phone' => $this->normalizePhone($phone),
                        'message'      => $message,
                        'from'         => config('services.eskiz.from'),
                    ]);
            }

            if ($response->failed()) {
                Log::channel('auth')->error('[SMS] Eskiz send failed', [
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 300),
                ]);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('[SMS] Eskiz error: ' . $e->getMessage());
            return false;
        }
    }

    private function getToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3600 * 23, function () {
            $response = Http::post(self::BASE_URL . '/auth/login', [
                'email'    => config('services.eskiz.email'),
                'password' => config('services.eskiz.password'),
            ]);

            return $response->json('data.token');
        });
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
