<?php

return [
    'auth_token' => env('SENTRY_AUTH_TOKEN'),
    'org'        => env('SENTRY_ORG', 'visabor'),
    'project'    => env('SENTRY_PROJECT', 'php-laravel'),
];
