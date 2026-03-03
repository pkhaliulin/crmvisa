<?php

namespace App\Support\Helpers;

use Sentry\Event;

class SentryScrubber
{
    private static array $piiFields = [
        'passport_number',
        'phone',
        'date_of_birth',
        'passport_expires_at',
        'password',
        'pin_hash',
        'token',
    ];

    public static function beforeSend(Event $event): ?Event
    {
        $request = $event->getRequest();

        if (! empty($request['data']) && is_array($request['data'])) {
            $request['data'] = self::scrub($request['data']);
            $event->setRequest($request);
        }

        return $event;
    }

    private static function scrub(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (in_array($key, self::$piiFields, true)) {
                $value = '[FILTERED]';
            } elseif (is_array($value)) {
                $value = self::scrub($value);
            }
        }

        return $data;
    }
}
