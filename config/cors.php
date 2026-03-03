<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'https://visabor.uz',
        'https://www.visabor.uz',
        'http://localhost:5173',
        'http://localhost:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-Locale',
        'Accept',
    ],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
