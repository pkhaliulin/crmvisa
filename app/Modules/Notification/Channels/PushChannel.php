<?php

namespace App\Modules\Notification\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PushChannel
{
    /**
     * Push notification stub.
     * When FCM/APNs is integrated, replace with real implementation.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toPush')) {
            return;
        }

        $message = $notification->toPush($notifiable);

        Log::channel('single')->info('Push Notification (stub)', [
            'user_id' => $notifiable->id ?? null,
            'title'   => $message['title'] ?? '',
            'body'    => $message['body'] ?? '',
        ]);
    }
}
