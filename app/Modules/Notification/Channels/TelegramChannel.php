<?php

namespace App\Modules\Notification\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    /**
     * Отправка уведомления через Telegram Bot API.
     * Использует токен агентства (если есть) или мастер-токен CRMBor.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $chatId = $notifiable->telegram_chat_id ?? null;
        if (! $chatId) {
            return;
        }

        $message = $notification->toTelegram($notifiable);

        // Выбор токена: агентский (white-label) или мастер CRMBor
        $botToken = $this->resolveBotToken($notifiable);
        if (! $botToken) {
            return;
        }

        try {
            Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $message['text'],
                'parse_mode' => $message['parse_mode'] ?? 'HTML',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function resolveBotToken(mixed $notifiable): ?string
    {
        // Пробуем достать токен из агентства клиента
        $agency = $notifiable->cases()?->first()?->agency
            ?? $notifiable->agency
            ?? null;

        if ($agency && $agency->telegram_bot_token) {
            return $agency->telegram_bot_token;
        }

        // Fallback — мастер-бот CRMBor
        return config('services.telegram.bot_token');
    }
}
