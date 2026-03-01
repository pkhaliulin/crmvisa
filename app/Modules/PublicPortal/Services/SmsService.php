<?php

namespace App\Modules\PublicPortal\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private const TOKEN_CACHE_KEY = 'eskiz_token';
    private const BASE_URL        = 'https://notify.eskiz.uz/api';

    /**
     * Отправить SMS через Eskiz.uz.
     * В dev-режиме только логирует, не отправляет реально.
     */
    public function send(string $phone, string $message): bool
    {
        if (app()->isLocal() || app()->environment('testing')) {
            Log::info("[SMS DEV] To: {$phone} | Message: {$message}");
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
                // Токен мог устареть — обновляем и пробуем ещё раз
                Cache::forget(self::TOKEN_CACHE_KEY);
                $token    = $this->getToken();
                $response = Http::withToken($token)
                    ->post(self::BASE_URL . '/message/sms/send', [
                        'mobile_phone' => $this->normalizePhone($phone),
                        'message'      => $message,
                        'from'         => config('services.eskiz.from'),
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
        // Приводим к формату: 998901234567 (без + и пробелов)
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
