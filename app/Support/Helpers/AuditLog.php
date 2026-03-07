<?php

namespace App\Support\Helpers;

use Illuminate\Support\Facades\Log;

class AuditLog
{
    public static function log(string $action, array $context = []): void
    {
        $context['ip'] = request()->ip();
        $context['user_agent'] = request()->userAgent();
        $context['timestamp'] = now()->toIso8601String();

        Log::channel('audit')->info($action, $context);
    }
}
