<?php

namespace App\Modules\Notification\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * SMS channel stub.
     * SMS всегда уходят от зарегистрированного sender ID (определяется в notification).
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
        $sender  = $message['sender'] ?? config('notification.brands.visabor.sms_sender', 'VisaBor');

        // STUB: log instead of sending
        Log::channel('single')->info('SMS Notification (stub)', [
            'to'      => $phone,
            'sender'  => $sender,
            'message' => $message['text'] ?? $message,
        ]);

        // TODO: Integrate Eskiz.uz
        // $this->eskiz->send($phone, $message['text'], $sender);
    }
}
