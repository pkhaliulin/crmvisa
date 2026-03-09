<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Бренды уведомлений
    |--------------------------------------------------------------------------
    |
    | VisaBor — клиентский бренд (B2C маркетплейс)
    | VisaCRM — агентский бренд (B2B SaaS)
    |
    | SMS отправляются от имени VisaBor (один зарегистрированный sender ID).
    | Email: у каждого бренда свой отправитель.
    | Агентство может указать свой notification_email в настройках.
    |
    */

    'brands' => [
        'visabor' => [
            'email'      => env('VISABOR_MAIL_FROM', 'noreply@visabor.uz'),
            'sms_sender' => env('VISABOR_SMS_SENDER', 'VisaBor'),
        ],
        'visacrm' => [
            'email'      => env('VISACRM_MAIL_FROM', 'noreply@visacrm.uz'),
            'sms_sender' => env('VISACRM_SMS_SENDER', 'VisaCRM'),
        ],
    ],

];
