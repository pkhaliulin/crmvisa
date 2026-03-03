<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'release' => trim(exec('git log --pretty="%h" -n1 HEAD') ?: ''),

    'environment' => env('APP_ENV', 'production'),

    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
    ],

    'tracing' => [
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_ENABLED', false),
        'queue_jobs' => true,
        'sql_queries' => true,
        'sql_origin' => true,
        'views' => true,
        'http_client_requests' => true,
    ],

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    // Data scrubbing — PII protection
    'before_send' => [App\Support\Helpers\SentryScrubber::class, 'beforeSend'],

    'send_default_pii' => false,

    // Sentry API (for monitoring panel)
    'auth_token' => env('SENTRY_AUTH_TOKEN'),
    'org'        => env('SENTRY_ORG', 'visabor'),
    'project'    => env('SENTRY_PROJECT', 'php-laravel'),
];
