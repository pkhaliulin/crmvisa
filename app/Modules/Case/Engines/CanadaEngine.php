<?php

namespace App\Modules\Case\Engines;

class CanadaEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'CA';
    }

    public function getCountryName(): string
    {
        return 'Канада';
    }

    public function isSchengen(): bool
    {
        return false;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'visitor', 'label' => 'Гостевая (Visitor Visa)', 'label_uz' => 'Mehmon (Visitor Visa)', 'schengen' => false],
            ['code' => 'study',   'label' => 'Учебная (Study Permit)',   'label_uz' => 'O\'qish (Study Permit)', 'schengen' => false],
            ['code' => 'work',    'label' => 'Рабочая (Work Permit)',    'label_uz' => 'Ish (Work Permit)',      'schengen' => false],
            ['code' => 'pr',      'label' => 'Постоянное резидентство (PR)', 'label_uz' => 'Doimiy rezidentlik (PR)', 'schengen' => false],
            ['code' => 'transit', 'label' => 'Транзитная',               'label_uz' => 'Tranzit',                'schengen' => false],
            ['code' => 'super',   'label' => 'Суперроды (Super Visa)',   'label_uz' => 'Super Visa',             'schengen' => false],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        $docs = [
            [
                'name'        => 'Загранпаспорт',
                'name_uz'     => 'Xorijiy passport',
                'requirement' => 'required',
                'description' => 'Действующий загранпаспорт (срок действия на весь период пребывания)',
            ],
            [
                'name'        => 'Фото 3.5x4.5',
                'name_uz'     => 'Surat 3.5x4.5',
                'requirement' => 'required',
                'description' => 'Цифровое фото по канадским стандартам',
            ],
            [
                'name'        => 'Выписка с банковского счета',
                'name_uz'     => 'Bank hisobidan ko\'chirma',
                'requirement' => 'required',
                'description' => 'Подтверждение финансовой состоятельности',
            ],
            [
                'name'        => 'Справка с места работы',
                'name_uz'     => 'Ish joyidan ma\'lumotnoma',
                'requirement' => 'required',
                'description' => 'С указанием должности, стажа и зарплаты',
            ],
            [
                'name'        => 'Цель поездки',
                'name_uz'     => 'Sayohat maqsadi',
                'requirement' => 'required',
                'description' => 'Письмо с объяснением цели визита',
            ],
        ];

        if ($visaType === 'visitor') {
            $docs[] = [
                'name'        => 'Приглашение от канадской стороны',
                'name_uz'     => 'Kanada tomonidan taklif',
                'requirement' => 'recommended',
                'description' => 'Letter of Invitation (если есть)',
            ];
        }

        if ($visaType === 'study') {
            $docs[] = [
                'name'        => 'Letter of Acceptance (LOA)',
                'name_uz'     => 'Qabul xati (LOA)',
                'requirement' => 'required',
                'description' => 'Письмо о зачислении от DLI (Designated Learning Institution)',
            ];
            $docs[] = [
                'name'        => 'Подтверждение финансов (GIC или спонсор)',
                'name_uz'     => 'Moliyaviy tasdiq (GIC yoki homiy)',
                'requirement' => 'required',
                'description' => 'GIC сертификат или письмо спонсора с финансовыми документами',
            ];
        }

        if ($visaType === 'work') {
            $docs[] = [
                'name'        => 'LMIA или освобождение от LMIA',
                'name_uz'     => 'LMIA yoki LMIA dan ozod qilish',
                'requirement' => 'required',
                'description' => 'Labour Market Impact Assessment или основание для освобождения',
            ];
            $docs[] = [
                'name'        => 'Job Offer / трудовой контракт',
                'name_uz'     => 'Ish taklifi / mehnat shartnomasi',
                'requirement' => 'required',
                'description' => 'Предложение о работе от канадского работодателя',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        // TODO: добавить специфичные поля канадской анкеты (IMM 5257)
        return $this->commonFormSteps();
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'visitor', 'transit' => 30,
            'study'              => 60,
            'work'               => 60,
            'pr'                 => 180,
            'super'              => 90,
            default              => 30,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://www.canada.ca/en/immigration-refugees-citizenship/services/application.html'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => null, // Канада не имеет посольства в Ташкенте, ближайшее в Анкаре
            'phone'         => null,
            'email'         => null,
            'website'       => 'https://www.canada.ca/en/immigration-refugees-citizenship.html',
            'working_hours' => null,
        ];
    }
}
