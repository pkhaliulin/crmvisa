<?php

namespace App\Modules\Case\Engines;

class USAEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'US';
    }

    public function getCountryName(): string
    {
        return 'США';
    }

    public function isSchengen(): bool
    {
        return false;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'B1_B2',   'label' => 'Гостевая/Деловая (B1/B2)', 'label_uz' => 'Mehmon/Ish (B1/B2)',   'schengen' => false],
            ['code' => 'F1',      'label' => 'Студенческая (F-1)',        'label_uz' => 'Talaba (F-1)',          'schengen' => false],
            ['code' => 'J1',      'label' => 'Обменная (J-1)',            'label_uz' => 'Almashinuv (J-1)',      'schengen' => false],
            ['code' => 'H1B',     'label' => 'Рабочая (H-1B)',            'label_uz' => 'Ish (H-1B)',            'schengen' => false],
            ['code' => 'L1',      'label' => 'Внутрикорпоративный перевод (L-1)', 'label_uz' => 'Korporativ ko\'chirish (L-1)', 'schengen' => false],
            ['code' => 'K1',      'label' => 'Виза невесты/жениха (K-1)', 'label_uz' => 'Kuyov/Kelin vizasi (K-1)', 'schengen' => false],
            ['code' => 'transit', 'label' => 'Транзитная (C)',             'label_uz' => 'Tranzit (C)',            'schengen' => false],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        $docs = [
            [
                'name'        => 'Загранпаспорт',
                'name_uz'     => 'Xorijiy passport',
                'requirement' => 'required',
                'description' => 'Действующий загранпаспорт (срок действия 6+ месяцев после возврата)',
            ],
            [
                'name'        => 'Фото 5x5 см',
                'name_uz'     => 'Surat 5x5 sm',
                'requirement' => 'required',
                'description' => 'Цветное фото на белом фоне, не старше 6 месяцев (US-формат 51x51 мм)',
            ],
            [
                'name'        => 'Подтверждение DS-160',
                'name_uz'     => 'DS-160 tasdig\'i',
                'requirement' => 'required',
                'description' => 'Страница подтверждения заполненной анкеты DS-160',
            ],
            [
                'name'        => 'Квитанция об оплате MRV-сбора',
                'name_uz'     => 'MRV to\'lov kvitansiyasi',
                'requirement' => 'required',
                'description' => 'Подтверждение оплаты консульского сбора ($185 для B/F/J)',
            ],
            [
                'name'        => 'Выписка с банковского счета',
                'name_uz'     => 'Bank hisobidan ko\'chirma',
                'requirement' => 'required',
                'description' => 'За последние 3-6 месяцев',
            ],
            [
                'name'        => 'Справка с места работы',
                'name_uz'     => 'Ish joyidan ma\'lumotnoma',
                'requirement' => 'required',
                'description' => 'С указанием должности, стажа и зарплаты',
            ],
        ];

        if ($visaType === 'F1') {
            $docs[] = [
                'name'        => 'Форма I-20',
                'name_uz'     => 'I-20 shakli',
                'requirement' => 'required',
                'description' => 'Certificate of Eligibility от учебного заведения',
            ];
            $docs[] = [
                'name'        => 'Оплата SEVIS (I-901)',
                'name_uz'     => 'SEVIS to\'lovi (I-901)',
                'requirement' => 'required',
                'description' => 'Подтверждение оплаты SEVIS fee ($350)',
            ];
        }

        if ($visaType === 'J1') {
            $docs[] = [
                'name'        => 'Форма DS-2019',
                'name_uz'     => 'DS-2019 shakli',
                'requirement' => 'required',
                'description' => 'Certificate of Eligibility от программы обмена',
            ];
        }

        if ($visaType === 'H1B') {
            $docs[] = [
                'name'        => 'Approved Petition (I-797)',
                'name_uz'     => 'Tasdiqlangan petitsiya (I-797)',
                'requirement' => 'required',
                'description' => 'Notice of Approval от USCIS',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    /**
     * DS-160 имеет свой формат анкеты.
     */
    public function getFormSteps(string $visaType): array
    {
        return [
            [
                'step'     => 1,
                'title'    => 'Личные данные (DS-160)',
                'title_uz' => 'Shaxsiy ma\'lumotlar (DS-160)',
                'fields'   => [
                    ['key' => 'surname', 'label' => 'Фамилия', 'type' => 'text', 'required' => true],
                    ['key' => 'given_names', 'label' => 'Имя', 'type' => 'text', 'required' => true],
                    ['key' => 'full_name_native', 'label' => 'ФИО на родном языке', 'type' => 'text', 'required' => true],
                    ['key' => 'birth_date', 'label' => 'Дата рождения', 'type' => 'date', 'required' => true],
                    ['key' => 'birth_city', 'label' => 'Город рождения', 'type' => 'text', 'required' => true],
                    ['key' => 'birth_country', 'label' => 'Страна рождения', 'type' => 'text', 'required' => true],
                    ['key' => 'nationality', 'label' => 'Гражданство', 'type' => 'text', 'required' => true],
                    ['key' => 'ssn', 'label' => 'SSN (если есть)', 'type' => 'text', 'required' => false],
                    ['key' => 'tax_id', 'label' => 'ИНН', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'step'     => 2,
                'title'    => 'Паспортные данные',
                'title_uz' => 'Passport ma\'lumotlari',
                'fields'   => [
                    ['key' => 'passport_number', 'label' => 'Номер паспорта', 'type' => 'text', 'required' => true],
                    ['key' => 'passport_book_number', 'label' => 'Номер книжки паспорта', 'type' => 'text', 'required' => false],
                    ['key' => 'passport_issue_date', 'label' => 'Дата выдачи', 'type' => 'date', 'required' => true],
                    ['key' => 'passport_expiry_date', 'label' => 'Дата окончания', 'type' => 'date', 'required' => true],
                    ['key' => 'passport_issued_country', 'label' => 'Страна выдачи', 'type' => 'text', 'required' => true],
                    ['key' => 'lost_passport', 'label' => 'Был ли утерян паспорт?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                ],
            ],
            [
                'step'     => 3,
                'title'    => 'Данные поездки',
                'title_uz' => 'Sayohat ma\'lumotlari',
                'fields'   => [
                    ['key' => 'visa_type', 'label' => 'Тип визы', 'type' => 'select', 'required' => true],
                    ['key' => 'travel_purpose', 'label' => 'Цель поездки', 'type' => 'text', 'required' => true],
                    ['key' => 'arrival_date', 'label' => 'Планируемая дата прибытия', 'type' => 'date', 'required' => true],
                    ['key' => 'stay_duration', 'label' => 'Планируемый срок пребывания', 'type' => 'text', 'required' => true],
                    ['key' => 'us_address', 'label' => 'Адрес в США', 'type' => 'textarea', 'required' => true],
                    ['key' => 'us_contact_name', 'label' => 'Контактное лицо в США', 'type' => 'text', 'required' => false],
                    ['key' => 'us_contact_phone', 'label' => 'Телефон контактного лица', 'type' => 'tel', 'required' => false],
                ],
            ],
            [
                'step'     => 4,
                'title'    => 'Работа и образование',
                'title_uz' => 'Ish va ta\'lim',
                'fields'   => [
                    ['key' => 'occupation', 'label' => 'Род деятельности', 'type' => 'text', 'required' => true],
                    ['key' => 'employer_name', 'label' => 'Работодатель', 'type' => 'text', 'required' => true],
                    ['key' => 'employer_address', 'label' => 'Адрес работодателя', 'type' => 'textarea', 'required' => true],
                    ['key' => 'employer_phone', 'label' => 'Телефон работодателя', 'type' => 'tel', 'required' => false],
                    ['key' => 'monthly_salary', 'label' => 'Месячный доход', 'type' => 'number', 'required' => false],
                    ['key' => 'education_level', 'label' => 'Уровень образования', 'type' => 'select', 'required' => true, 'options' => ['secondary', 'bachelor', 'master', 'phd', 'other']],
                ],
            ],
            [
                'step'     => 5,
                'title'    => 'Безопасность и иммиграция',
                'title_uz' => 'Xavfsizlik va immigratsiya',
                'fields'   => [
                    ['key' => 'previous_us_visa', 'label' => 'Были ли визы США ранее?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                    ['key' => 'us_visa_refusal', 'label' => 'Были ли отказы?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                    ['key' => 'us_visa_refusal_details', 'label' => 'Детали отказов', 'type' => 'textarea', 'required' => false],
                    ['key' => 'relatives_in_us', 'label' => 'Есть ли родственники в США?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                    ['key' => 'criminal_record', 'label' => 'Судимость', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                ],
            ],
        ];
    }

    public function getProcessingStages(): array
    {
        return [
            ['slug' => 'ds160_filling',        'title' => 'Заполнение DS-160',      'title_uz' => 'DS-160 to\'ldirish',    'order' => 1],
            ['slug' => 'mrv_payment',           'title' => 'Оплата MRV-сбора',       'title_uz' => 'MRV to\'lovini to\'lash', 'order' => 2],
            ['slug' => 'document_collection',   'title' => 'Сбор документов',        'title_uz' => 'Hujjatlarni yig\'ish',  'order' => 3],
            ['slug' => 'interview_appointment', 'title' => 'Запись на собеседование','title_uz' => 'Suhbatga yozilish',     'order' => 4],
            ['slug' => 'interview',             'title' => 'Собеседование',          'title_uz' => 'Suhbat',                'order' => 5],
            ['slug' => 'processing',            'title' => 'Рассмотрение',           'title_uz' => 'Ko\'rib chiqish',       'order' => 6],
            ['slug' => 'result',                'title' => 'Результат',              'title_uz' => 'Natija',                'order' => 7],
        ];
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'B1_B2', 'transit'       => 14,
            'F1', 'J1'              => 21,
            'H1B', 'L1'            => 60,
            'K1'                    => 180,
            default                 => 21,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://ceac.state.gov/genniv/'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Мойкуд, 3, Ташкент 100093, Узбекистан',
            'phone'         => '+998 71 120 54 50',
            'email'         => null, // Посольство США не публикует email для визовых вопросов
            'website'       => 'https://uz.usembassy.gov',
            'working_hours' => 'Пн-Пт: 08:00-17:00 (собеседования: 08:00-12:00)',
        ];
    }
}
