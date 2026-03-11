<?php

namespace App\Modules\Notification\Channels;

use App\Modules\PublicPortal\Services\SmsService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function __construct(private SmsService $sms) {}

    /**
     * Отправить SMS-уведомление через Eskiz.uz.
     * Клиенты получают SMS от VisaBor, агентства — от VisaCRM.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $phone = $notifiable->phone ?? null;
        if (!$phone) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $text    = $message['text'] ?? (is_string($message) ? $message : '');

        if (empty($text)) {
            return;
        }

        $sent = $this->sms->send($phone, $text);

        if (!$sent) {
            Log::channel('auth')->warning('[SmsChannel] Failed to send SMS', [
                'phone' => mb_substr($phone, 0, 4) . '***' . mb_substr($phone, -4),
            ]);
        }
    }
}
