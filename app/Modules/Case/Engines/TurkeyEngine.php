<?php

namespace App\Modules\Case\Engines;

class TurkeyEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'TR';
    }

    public function getCountryName(): string
    {
        return 'Турция';
    }

    public function isSchengen(): bool
    {
        return false;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'e_visa',   'label' => 'Электронная виза (e-Visa)',  'label_uz' => 'Elektron viza (e-Visa)',  'schengen' => false],
            ['code' => 'tourism',  'label' => 'Туристическая (стикер)',     'label_uz' => 'Turistik (stiker)',        'schengen' => false],
            ['code' => 'business', 'label' => 'Деловая',                    'label_uz' => 'Ishbilarmonlik',           'schengen' => false],
            ['code' => 'work',     'label' => 'Рабочая (Calisma Izni)',     'label_uz' => 'Ish (Calisma Izni)',       'schengen' => false],
            ['code' => 'student',  'label' => 'Студенческая (Ogrenci)',     'label_uz' => 'Talaba (Ogrenci)',         'schengen' => false],
            ['code' => 'family',   'label' => 'Семейная (Aile Birlesimi)',  'label_uz' => 'Oilaviy (Aile Birlesimi)', 'schengen' => false],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        if ($visaType === 'e_visa') {
            return [
                [
                    'name'        => 'Загранпаспорт',
                    'name_uz'     => 'Xorijiy passport',
                    'requirement' => 'required',
                    'description' => 'Действующий загранпаспорт (срок действия 6+ месяцев)',
                ],
                [
                    'name'        => 'Оплата e-Visa',
                    'name_uz'     => 'e-Visa to\'lovi',
                    'requirement' => 'required',
                    'description' => 'Оплата онлайн на сайте e-Visa ($50 USD)',
                ],
            ];
        }

        $docs = $this->commonDocuments();

        $docs[] = [
            'name'        => 'Бронь отеля',
            'name_uz'     => 'Mehmonxona bron',
            'requirement' => 'required',
            'description' => 'Подтверждение проживания в Турции',
        ];
        $docs[] = [
            'name'        => 'Бронь авиабилетов',
            'name_uz'     => 'Aviabilet bron',
            'requirement' => 'required',
            'description' => 'Туда и обратно',
        ];

        if ($visaType === 'work') {
            $docs[] = [
                'name'        => 'Разрешение на работу (Calisma Izni)',
                'name_uz'     => 'Ish ruxsati (Calisma Izni)',
                'requirement' => 'required',
                'description' => 'Одобренное разрешение на работу от Министерства труда Турции',
            ];
        }

        if ($visaType === 'student') {
            $docs[] = [
                'name'        => 'Подтверждение зачисления',
                'name_uz'     => 'Qabul tasdig\'i',
                'requirement' => 'required',
                'description' => 'Kabul Mektubu от турецкого университета',
            ];
        }

        if ($visaType === 'business') {
            $docs[] = [
                'name'        => 'Приглашение от турецкой компании',
                'name_uz'     => 'Turkiya kompaniyasidan taklif',
                'requirement' => 'required',
                'description' => 'Davetiye с указанием цели визита',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        if ($visaType === 'e_visa') {
            return [
                [
                    'step'     => 1,
                    'title'    => 'Данные для e-Visa',
                    'title_uz' => 'e-Visa uchun ma\'lumotlar',
                    'fields'   => [
                        ['key' => 'surname', 'label' => 'Фамилия', 'type' => 'text', 'required' => true],
                        ['key' => 'given_names', 'label' => 'Имя', 'type' => 'text', 'required' => true],
                        ['key' => 'birth_date', 'label' => 'Дата рождения', 'type' => 'date', 'required' => true],
                        ['key' => 'nationality', 'label' => 'Гражданство', 'type' => 'text', 'required' => true],
                        ['key' => 'passport_number', 'label' => 'Номер паспорта', 'type' => 'text', 'required' => true],
                        ['key' => 'passport_expiry', 'label' => 'Срок действия паспорта', 'type' => 'date', 'required' => true],
                        ['key' => 'arrival_date', 'label' => 'Дата прибытия', 'type' => 'date', 'required' => true],
                        ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                    ],
                ],
            ];
        }

        // TODO: добавить специфичные поля турецкой анкеты (стикер-виза)
        return $this->commonFormSteps();
    }

    public function getProcessingStages(): array
    {
        return [
            ['slug' => 'document_collection', 'title' => 'Сбор документов',      'title_uz' => 'Hujjatlarni yig\'ish', 'order' => 1],
            ['slug' => 'form_filling',        'title' => 'Заполнение анкеты',    'title_uz' => 'Anketani to\'ldirish',  'order' => 2],
            ['slug' => 'review',              'title' => 'Проверка комплекта',   'title_uz' => 'Hujjatlarni tekshirish','order' => 3],
            ['slug' => 'submission',          'title' => 'Подача в консульство', 'title_uz' => 'Konsullikka topshirish','order' => 4],
            ['slug' => 'processing',          'title' => 'Рассмотрение',         'title_uz' => 'Ko\'rib chiqish',       'order' => 5],
            ['slug' => 'result',              'title' => 'Результат',            'title_uz' => 'Natija',                'order' => 6],
        ];
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'e_visa'                => 1,
            'tourism', 'business'  => 7,
            'work'                 => 30,
            'student'              => 14,
            'family'               => 30,
            default                => 7,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://www.evisa.gov.tr'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Гоголя, 87, Ташкент 100015, Узбекистан',
            'phone'         => '+998 71 233 36 37',
            'email'         => 'embassy.tashkent@mfa.gov.tr',
            'website'       => 'http://taskent.be.mfa.gov.tr',
            'working_hours' => 'Пн-Пт: 09:00-12:30, 14:00-17:00',
        ];
    }
}
