<?php

namespace App\Modules\Case\Engines;

class UKEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'GB';
    }

    public function getCountryName(): string
    {
        return 'Великобритания';
    }

    public function isSchengen(): bool
    {
        return false;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'visitor',  'label' => 'Гостевая (Visitor)',    'label_uz' => 'Mehmon (Visitor)',     'schengen' => false],
            ['code' => 'business', 'label' => 'Деловая (Business)',    'label_uz' => 'Ishbilarmonlik',       'schengen' => false],
            ['code' => 'student',  'label' => 'Студенческая (Tier 4)', 'label_uz' => 'Talaba (Tier 4)',      'schengen' => false],
            ['code' => 'work',     'label' => 'Рабочая (Skilled Worker)', 'label_uz' => 'Ish (Skilled Worker)', 'schengen' => false],
            ['code' => 'family',   'label' => 'Семейная',              'label_uz' => 'Oilaviy',              'schengen' => false],
            ['code' => 'transit',  'label' => 'Транзитная',            'label_uz' => 'Tranzit',              'schengen' => false],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        $docs = $this->commonDocuments();

        // Великобритания не требует мед. страховку для гостевых виз, но документ уже в common
        $docs[] = [
            'name'        => 'Бронь отеля / письмо от принимающей стороны',
            'name_uz'     => 'Mehmonxona bron / qabul qiluvchi tomondan xat',
            'requirement' => 'required',
            'description' => 'Подтверждение проживания в Великобритании',
        ];
        $docs[] = [
            'name'        => 'Бронь авиабилетов',
            'name_uz'     => 'Aviabilet bron',
            'requirement' => 'recommended',
            'description' => 'Рекомендуется, но не обязательно при подаче',
        ];
        $docs[] = [
            'name'        => 'Выписка по банковскому счёту (28 дней)',
            'name_uz'     => 'Bank hisobidan ko\'chirma (28 kun)',
            'requirement' => 'required',
            'description' => 'За последние 28 дней, подтверждение достаточных средств',
        ];

        if ($visaType === 'student') {
            $docs[] = [
                'name'        => 'CAS (Confirmation of Acceptance for Studies)',
                'name_uz'     => 'CAS (O\'qishga qabul tasdig\'i)',
                'requirement' => 'required',
                'description' => 'CAS-номер от спонсора-учебного заведения',
            ];
            $docs[] = [
                'name'        => 'Сертификат IELTS/TOEFL',
                'name_uz'     => 'IELTS/TOEFL sertifikati',
                'requirement' => 'required',
                'description' => 'Подтверждение уровня английского языка',
            ];
        }

        if ($visaType === 'work') {
            $docs[] = [
                'name'        => 'Certificate of Sponsorship (CoS)',
                'name_uz'     => 'Homiylik sertifikati (CoS)',
                'requirement' => 'required',
                'description' => 'CoS-номер от работодателя с лицензией спонсора',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        // TODO: добавить специфичные поля UK-анкеты (online application)
        $steps = $this->commonFormSteps();

        // UK имеет дополнительные вопросы об иммиграционной истории
        $steps[] = [
            'step'     => 5,
            'title'    => 'Иммиграционная история',
            'title_uz' => 'Immigratsiya tarixi',
            'fields'   => [
                ['key' => 'previous_uk_visa', 'label' => 'Были ли визы UK ранее?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                ['key' => 'uk_refusal_history', 'label' => 'Были ли отказы UK?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                ['key' => 'uk_refusal_details', 'label' => 'Детали отказов', 'type' => 'textarea', 'required' => false],
                ['key' => 'criminal_record', 'label' => 'Судимость', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
                ['key' => 'overstay_history', 'label' => 'Были ли нарушения визового режима?', 'type' => 'select', 'required' => true, 'options' => ['yes', 'no']],
            ],
        ];

        return $steps;
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'visitor', 'business', 'transit' => 15,
            'student'                        => 21,
            'work'                           => 21,
            'family'                         => 60,
            default                          => 15,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://www.gov.uk/apply-to-come-to-the-uk'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Гуламова, 67, Ташкент 100015, Узбекистан',
            'phone'         => '+998 71 120 15 00',
            'email'         => null,
            'website'       => 'https://www.gov.uk/world/uzbekistan',
            'working_hours' => 'Пн-Пт: 09:00-17:00',
        ];
    }
}
