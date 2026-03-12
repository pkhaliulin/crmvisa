<?php

namespace App\Modules\Case\Engines;

class FranceEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'FR';
    }

    public function getCountryName(): string
    {
        return 'Франция';
    }

    public function isSchengen(): bool
    {
        return true;
    }

    public function getStatus(): string
    {
        return 'pilot';
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'tourism',  'label' => 'Туристическая', 'label_uz' => 'Turistik',  'schengen' => true],
            ['code' => 'business', 'label' => 'Деловая',       'label_uz' => 'Ishbilarmonlik', 'schengen' => true],
            ['code' => 'study',    'label' => 'Учебная',       'label_uz' => 'O\'qish',   'schengen' => true],
            ['code' => 'work',     'label' => 'Рабочая',       'label_uz' => 'Ish',       'schengen' => true],
            ['code' => 'transit',  'label' => 'Транзитная',    'label_uz' => 'Tranzit',   'schengen' => true],
            ['code' => 'family',   'label' => 'Семейная',      'label_uz' => 'Oilaviy',   'schengen' => true],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        $docs = $this->commonDocuments();

        $docs[] = [
            'name'        => 'Бронь отеля / приглашение',
            'name_uz'     => 'Mehmonxona bron / taklif',
            'requirement' => 'required',
            'description' => 'Подтверждение проживания на весь срок пребывания',
        ];
        $docs[] = [
            'name'        => 'Бронь авиабилетов',
            'name_uz'     => 'Aviabilet bron',
            'requirement' => 'required',
            'description' => 'Туда и обратно',
        ];

        if ($visaType === 'business') {
            $docs[] = [
                'name'        => 'Приглашение от французской компании',
                'name_uz'     => 'Fransuz kompaniyasidan taklif',
                'requirement' => 'required',
                'description' => 'Официальное письмо-приглашение с указанием цели визита',
            ];
        }

        if ($visaType === 'study') {
            $docs[] = [
                'name'        => 'Подтверждение зачисления (Campus France)',
                'name_uz'     => 'Qabul qilinganlik tasdig\'i (Campus France)',
                'requirement' => 'required',
                'description' => 'Attestation de pré-inscription от университета',
            ];
        }

        return $docs;
    }

    /**
     * Шаги формы France-Visas (~60 полей, 6 шагов).
     * Полные маппинги хранятся в visa_form_field_mappings через VisaCaseRule.
     */
    public function getFormSteps(string $visaType): array
    {
        return [
            [
                'step'     => 1,
                'title'    => 'Личные данные',
                'title_uz' => 'Shaxsiy ma\'lumotlar',
                'fields'   => [
                    ['key' => 'surname', 'label' => 'Фамилия (как в паспорте)', 'type' => 'text', 'required' => true],
                    ['key' => 'given_names', 'label' => 'Имя (как в паспорте)', 'type' => 'text', 'required' => true],
                    ['key' => 'maiden_name', 'label' => 'Девичья фамилия', 'type' => 'text', 'required' => false],
                    ['key' => 'birth_date', 'label' => 'Дата рождения', 'type' => 'date', 'required' => true],
                    ['key' => 'birth_place', 'label' => 'Место рождения', 'type' => 'text', 'required' => true],
                    ['key' => 'birth_country', 'label' => 'Страна рождения', 'type' => 'text', 'required' => true],
                    ['key' => 'nationality_current', 'label' => 'Текущее гражданство', 'type' => 'text', 'required' => true],
                    ['key' => 'nationality_birth', 'label' => 'Гражданство при рождении', 'type' => 'text', 'required' => true],
                    ['key' => 'gender', 'label' => 'Пол', 'type' => 'select', 'required' => true, 'options' => ['male', 'female']],
                    ['key' => 'marital_status', 'label' => 'Семейное положение', 'type' => 'select', 'required' => true, 'options' => ['single', 'married', 'divorced', 'widowed', 'separated', 'civil_union']],
                ],
            ],
            [
                'step'     => 2,
                'title'    => 'Паспортные данные',
                'title_uz' => 'Passport ma\'lumotlari',
                'fields'   => [
                    ['key' => 'passport_type', 'label' => 'Тип документа', 'type' => 'select', 'required' => true, 'options' => ['ordinary', 'diplomatic', 'service', 'official']],
                    ['key' => 'passport_number', 'label' => 'Номер паспорта', 'type' => 'text', 'required' => true],
                    ['key' => 'passport_issue_date', 'label' => 'Дата выдачи', 'type' => 'date', 'required' => true],
                    ['key' => 'passport_expiry_date', 'label' => 'Действителен до', 'type' => 'date', 'required' => true],
                    ['key' => 'passport_issued_by', 'label' => 'Орган выдачи', 'type' => 'text', 'required' => true],
                    ['key' => 'passport_issued_country', 'label' => 'Страна выдачи', 'type' => 'text', 'required' => true],
                ],
            ],
            [
                'step'     => 3,
                'title'    => 'Контактные данные',
                'title_uz' => 'Bog\'lanish ma\'lumotlari',
                'fields'   => [
                    ['key' => 'home_address', 'label' => 'Домашний адрес', 'type' => 'textarea', 'required' => true],
                    ['key' => 'postal_code', 'label' => 'Почтовый индекс', 'type' => 'text', 'required' => true],
                    ['key' => 'city', 'label' => 'Город', 'type' => 'text', 'required' => true],
                    ['key' => 'country_residence', 'label' => 'Страна проживания', 'type' => 'text', 'required' => true],
                    ['key' => 'phone', 'label' => 'Телефон', 'type' => 'tel', 'required' => true],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ],
            ],
            [
                'step'     => 4,
                'title'    => 'Данные поездки',
                'title_uz' => 'Sayohat ma\'lumotlari',
                'fields'   => [
                    ['key' => 'visa_type_requested', 'label' => 'Тип визы', 'type' => 'select', 'required' => true],
                    ['key' => 'entries_requested', 'label' => 'Кратность въезда', 'type' => 'select', 'required' => true, 'options' => ['single', 'double', 'multiple']],
                    ['key' => 'entry_date', 'label' => 'Дата въезда', 'type' => 'date', 'required' => true],
                    ['key' => 'exit_date', 'label' => 'Дата выезда', 'type' => 'date', 'required' => true],
                    ['key' => 'duration_days', 'label' => 'Количество дней', 'type' => 'number', 'required' => true],
                    ['key' => 'main_destination', 'label' => 'Основная страна назначения', 'type' => 'text', 'required' => true],
                    ['key' => 'other_destinations', 'label' => 'Другие страны Шенгена', 'type' => 'text', 'required' => false],
                    ['key' => 'first_entry_country', 'label' => 'Страна первого въезда', 'type' => 'text', 'required' => true],
                    ['key' => 'travel_purpose', 'label' => 'Цель поездки', 'type' => 'select', 'required' => true],
                    ['key' => 'accommodation_type', 'label' => 'Тип проживания', 'type' => 'select', 'required' => true, 'options' => ['hotel', 'private', 'company', 'other']],
                    ['key' => 'accommodation_address', 'label' => 'Адрес проживания', 'type' => 'textarea', 'required' => true],
                ],
            ],
            [
                'step'     => 5,
                'title'    => 'Финансирование и работа',
                'title_uz' => 'Moliyalashtirish va ish',
                'fields'   => [
                    ['key' => 'employer_name', 'label' => 'Название организации', 'type' => 'text', 'required' => true],
                    ['key' => 'employer_address', 'label' => 'Адрес организации', 'type' => 'textarea', 'required' => true],
                    ['key' => 'employer_phone', 'label' => 'Телефон организации', 'type' => 'tel', 'required' => false],
                    ['key' => 'position', 'label' => 'Должность', 'type' => 'text', 'required' => true],
                    ['key' => 'trip_costs_by', 'label' => 'Расходы несёт', 'type' => 'select', 'required' => true, 'options' => ['self', 'sponsor', 'company', 'inviting_party']],
                    ['key' => 'means_of_support', 'label' => 'Средства к существованию', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'step'     => 6,
                'title'    => 'Предыдущие визы и Шенген',
                'title_uz' => 'Oldingi vizalar va Shengen',
                'fields'   => [
                    ['key' => 'previous_schengen', 'label' => 'Были ли шенгенские визы?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                    ['key' => 'previous_schengen_dates', 'label' => 'Даты предыдущих виз', 'type' => 'text', 'required' => false],
                    ['key' => 'fingerprints_collected', 'label' => 'Сдавались ли отпечатки пальцев?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                    ['key' => 'fingerprints_date', 'label' => 'Дата сдачи отпечатков', 'type' => 'date', 'required' => false],
                    ['key' => 'entry_permit_return', 'label' => 'Разрешение на возврат', 'type' => 'text', 'required' => false],
                    ['key' => 'refusal_history', 'label' => 'Были ли отказы?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                    ['key' => 'refusal_details', 'label' => 'Детали отказов', 'type' => 'textarea', 'required' => false],
                ],
            ],
        ];
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'tourism', 'business', 'transit' => 15,
            'study'                          => 30,
            'work'                           => 60,
            'family'                         => 30,
            default                          => 15,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://france-visas.gouv.fr'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Истиклол, 25, Ташкент, Узбекистан',
            'phone'         => '+998 71 233 53 82',
            'email'         => 'ambafrance.tachkent-amba@diplomatie.gouv.fr',
            'website'       => 'https://uz.ambafrance.org',
            'working_hours' => 'Пн-Пт: 09:00-12:00, 14:00-17:00',
        ];
    }
}
