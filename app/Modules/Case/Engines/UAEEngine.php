<?php

namespace App\Modules\Case\Engines;

class UAEEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'AE';
    }

    public function getCountryName(): string
    {
        return 'ОАЭ';
    }

    public function isSchengen(): bool
    {
        return false;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'tourism',  'label' => 'Туристическая (30/60/90 дней)', 'label_uz' => 'Turistik (30/60/90 kun)', 'schengen' => false],
            ['code' => 'business', 'label' => 'Деловая',                       'label_uz' => 'Ishbilarmonlik',           'schengen' => false],
            ['code' => 'work',     'label' => 'Рабочая (Employment Visa)',      'label_uz' => 'Ish (Employment Visa)',    'schengen' => false],
            ['code' => 'transit',  'label' => 'Транзитная (48/96 часов)',       'label_uz' => 'Tranzit (48/96 soat)',     'schengen' => false],
            ['code' => 'student',  'label' => 'Студенческая',                  'label_uz' => 'Talaba',                   'schengen' => false],
            ['code' => 'family',   'label' => 'Семейная (Residence)',           'label_uz' => 'Oilaviy (Residence)',      'schengen' => false],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        $docs = [
            [
                'name'        => 'Загранпаспорт',
                'name_uz'     => 'Xorijiy passport',
                'requirement' => 'required',
                'description' => 'Действующий загранпаспорт (срок действия 6+ месяцев)',
            ],
            [
                'name'        => 'Фото 4.3x5.5',
                'name_uz'     => 'Surat 4.3x5.5',
                'requirement' => 'required',
                'description' => 'Цветное фото на белом фоне (формат ОАЭ)',
            ],
            [
                'name'        => 'Бронь отеля / приглашение',
                'name_uz'     => 'Mehmonxona bron / taklif',
                'requirement' => 'required',
                'description' => 'Подтверждение проживания в ОАЭ',
            ],
            [
                'name'        => 'Бронь авиабилетов',
                'name_uz'     => 'Aviabilet bron',
                'requirement' => 'required',
                'description' => 'Туда и обратно',
            ],
            [
                'name'        => 'Выписка с банковского счета',
                'name_uz'     => 'Bank hisobidan ko\'chirma',
                'requirement' => 'required',
                'description' => 'Подтверждение финансовой состоятельности',
            ],
        ];

        if ($visaType === 'work') {
            $docs[] = [
                'name'        => 'Предложение о работе',
                'name_uz'     => 'Ish taklifi',
                'requirement' => 'required',
                'description' => 'Offer letter от компании ОАЭ с лицензией',
            ];
            $docs[] = [
                'name'        => 'Медицинская справка',
                'name_uz'     => 'Tibbiy ma\'lumotnoma',
                'requirement' => 'required',
                'description' => 'Медосмотр от аккредитованного центра',
            ];
        }

        if ($visaType === 'business') {
            $docs[] = [
                'name'        => 'Приглашение от компании в ОАЭ',
                'name_uz'     => 'BAA kompaniyasidan taklif',
                'requirement' => 'required',
                'description' => 'Письмо-приглашение с копией trade license',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        // TODO: добавить специфичные поля анкеты для ОАЭ
        return $this->commonFormSteps();
    }

    public function getProcessingStages(): array
    {
        return [
            ['slug' => 'document_collection', 'title' => 'Сбор документов',     'title_uz' => 'Hujjatlarni yig\'ish', 'order' => 1],
            ['slug' => 'application_submit',  'title' => 'Подача заявки онлайн','title_uz' => 'Arizani onlayn topshirish', 'order' => 2],
            ['slug' => 'processing',          'title' => 'Рассмотрение',        'title_uz' => 'Ko\'rib chiqish',       'order' => 3],
            ['slug' => 'e_visa_issued',       'title' => 'e-Visa выдана',       'title_uz' => 'e-Visa berildi',        'order' => 4],
            ['slug' => 'result',              'title' => 'Результат',           'title_uz' => 'Natija',                'order' => 5],
        ];
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'tourism', 'transit'   => 3,
            'business'             => 5,
            'work'                 => 14,
            'student', 'family'    => 14,
            default                => 5,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://smartservices.icp.gov.ae'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Гоголя, 10, Ташкент, Узбекистан',
            'phone'         => '+998 71 140 11 21',
            'email'         => 'tashkent@mofa.gov.ae',
            'website'       => 'https://www.mofaic.gov.ae',
            'working_hours' => 'Пн-Пт: 09:00-14:00',
        ];
    }
}
