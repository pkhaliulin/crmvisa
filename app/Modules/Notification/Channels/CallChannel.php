<?php

namespace App\Modules\Notification\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CallChannel
{
    /**
     * Phone call notification stub.
     * When Twilio/VoIP is integrated, replace with real implementation.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toCall')) {
            return;
        }

        $phone = $notifiable->phone ?? null;
        if (!$phone) {
            return;
        }

        $message = $notification->toCall($notifiable);

        Log::channel('single')->info('Call Notification (stub)', [
            'to'      => $phone,
            'message' => $message['text'] ?? $message,
        ]);
    }
}
