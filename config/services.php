<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token'      => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // SMS_STUB_PIN=6236 → принимает только этот код, SMS не отправляется
    // Убрать из .env когда подключите реальный SMS-провайдер
    'sms_stub' => [
        'pin' => env('SMS_STUB_PIN'),
    ],

    'eskiz' => [
        'email'    => env('ESKIZ_EMAIL'),
        'password' => env('ESKIZ_PASSWORD'),
        'from'     => env('ESKIZ_FROM', '4546'),   // имя отправителя
    ],

    'payme' => [
        'merchant_id'  => env('PAYME_MERCHANT_ID', ''),
        'merchant_key' => env('PAYME_MERCHANT_KEY', ''),
        'key'          => env('PAYME_KEY', ''),
        'test_key'     => env('PAYME_TEST_KEY', ''),
        'test_mode'    => env('PAYME_TEST_MODE', false),
    ],

    'click' => [
        'merchant_id'      => env('CLICK_MERCHANT_ID', ''),
        'service_id'       => env('CLICK_SERVICE_ID', ''),
        'merchant_user_id' => env('CLICK_MERCHANT_USER_ID', ''),
        'secret_key'       => env('CLICK_SECRET_KEY', ''),
    ],

    'uzum' => [
        'terminal_id' => env('UZUM_TERMINAL_ID', ''),
        'secret_key'  => env('UZUM_SECRET_KEY', ''),
        'service_id'  => env('UZUM_SERVICE_ID', ''),
    ],

    // OCR — распознавание паспортов (мульти-провайдер)
    'ocr' => [
        'provider'                => env('OCR_PROVIDER', 'openai'), // openai (gpt-4o-mini), claude, google, mindee
        'anthropic_key'           => env('ANTHROPIC_API_KEY', ''),
        'openai_key'              => env('OPENAI_API_KEY', ''),
        'google_credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS', ''),
        'mindee_key'              => env('MINDEE_API_KEY', ''),
    ],

];
