<?php

namespace App\Modules\Case\Engines;

class SouthKoreaEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'KR';
    }

    public function getCountryName(): string
    {
        return 'Южная Корея';
    }

    public function isSchengen(): bool
    {
        return false;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'tourism',  'label' => 'Туристическая (C-3)', 'label_uz' => 'Turistik (C-3)',       'schengen' => false],
            ['code' => 'business', 'label' => 'Деловая (C-3-4)',     'label_uz' => 'Ishbilarmonlik (C-3-4)', 'schengen' => false],
            ['code' => 'work',     'label' => 'Рабочая (E серия)',    'label_uz' => 'Ish (E seriyasi)',       'schengen' => false],
            ['code' => 'study',    'label' => 'Учебная (D-2)',       'label_uz' => 'O\'qish (D-2)',          'schengen' => false],
            ['code' => 'medical',  'label' => 'Медицинская (C-3-3)', 'label_uz' => 'Tibbiy (C-3-3)',        'schengen' => false],
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
                'name'        => 'Фото 3.5x4.5',
                'name_uz'     => 'Surat 3.5x4.5',
                'requirement' => 'required',
                'description' => 'Цветное фото на белом фоне',
            ],
            [
                'name'        => 'Анкета визы',
                'name_uz'     => 'Viza anketasi',
                'requirement' => 'required',
                'description' => 'Заполненная на корейском или английском языке',
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
        ];

        if ($visaType === 'tourism') {
            $docs[] = [
                'name'        => 'Бронь отеля',
                'name_uz'     => 'Mehmonxona bron',
                'requirement' => 'required',
                'description' => 'Подтверждение проживания',
            ];
            $docs[] = [
                'name'        => 'Бронь авиабилетов',
                'name_uz'     => 'Aviabilet bron',
                'requirement' => 'required',
                'description' => 'Туда и обратно',
            ];
        }

        if ($visaType === 'study') {
            $docs[] = [
                'name'        => 'Подтверждение зачисления',
                'name_uz'     => 'Qabul tasdig\'i',
                'requirement' => 'required',
                'description' => 'Стандартный номер зачисления от корейского учебного заведения',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        // TODO: добавить специфичные поля корейской визовой анкеты
        return $this->commonFormSteps();
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'tourism', 'business' => 7,
            'study'               => 14,
            'work'                => 30,
            'medical'             => 7,
            default               => 10,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://www.visa.go.kr'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Афросиаб, 7-А, Ташкент 100031, Узбекистан',
            'phone'         => '+998 71 252 01 17',
            'email'         => null,
            'website'       => 'https://overseas.mofa.go.kr/uz-ru',
            'working_hours' => 'Пн-Пт: 09:00-12:00, 14:00-17:00',
        ];
    }
}
