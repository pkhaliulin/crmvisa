<?php

namespace App\Modules\Notification\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * SMS channel stub.
     * When Eskiz.uz is integrated, replace with real implementation.
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

        // STUB: log instead of sending
        Log::channel('single')->info('SMS Notification (stub)', [
            'to'      => $phone,
            'message' => $message['text'] ?? $message,
        ]);

        // TODO: Integrate Eskiz.uz
        // $this->eskiz->send($phone, $message['text']);
    }
}
