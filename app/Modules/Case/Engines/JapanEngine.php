<?php

namespace App\Modules\Case\Engines;

class JapanEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'JP';
    }

    public function getCountryName(): string
    {
        return 'Япония';
    }

    public function isSchengen(): bool
    {
        return false;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'tourism',  'label' => 'Туристическая',     'label_uz' => 'Turistik',       'schengen' => false],
            ['code' => 'business', 'label' => 'Деловая',           'label_uz' => 'Ishbilarmonlik',  'schengen' => false],
            ['code' => 'transit',  'label' => 'Транзитная',        'label_uz' => 'Tranzit',         'schengen' => false],
            ['code' => 'study',    'label' => 'Учебная',           'label_uz' => 'O\'qish',         'schengen' => false],
            ['code' => 'work',     'label' => 'Рабочая',           'label_uz' => 'Ish',             'schengen' => false],
            ['code' => 'cultural', 'label' => 'Культурная',        'label_uz' => 'Madaniy',         'schengen' => false],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        $docs = [
            [
                'name'        => 'Загранпаспорт',
                'name_uz'     => 'Xorijiy passport',
                'requirement' => 'required',
                'description' => 'Действующий загранпаспорт с минимум 2 пустыми страницами',
            ],
            [
                'name'        => 'Фото 4.5x4.5',
                'name_uz'     => 'Surat 4.5x4.5',
                'requirement' => 'required',
                'description' => 'Цветное фото на белом фоне (японский формат 45x45 мм)',
            ],
            [
                'name'        => 'Анкета визы',
                'name_uz'     => 'Viza anketasi',
                'requirement' => 'required',
                'description' => 'Заполненная визовая анкета по форме посольства',
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
                'name'        => 'План поездки (программа)',
                'name_uz'     => 'Sayohat rejasi (dastur)',
                'requirement' => 'required',
                'description' => 'Подробный маршрут по дням',
            ],
        ];

        if ($visaType === 'tourism') {
            $docs[] = [
                'name'        => 'Бронь отеля',
                'name_uz'     => 'Mehmonxona bron',
                'requirement' => 'required',
                'description' => 'Подтверждение проживания на весь срок',
            ];
            $docs[] = [
                'name'        => 'Бронь авиабилетов',
                'name_uz'     => 'Aviabilet bron',
                'requirement' => 'required',
                'description' => 'Туда и обратно',
            ];
        }

        if ($visaType === 'business') {
            $docs[] = [
                'name'        => 'Приглашение от японской компании (Shoheijou)',
                'name_uz'     => 'Yaponiya kompaniyasidan taklif (Shoheijou)',
                'requirement' => 'required',
                'description' => 'Письмо-приглашение (shoheijou) + гарантийное письмо (hoshoujou)',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        // TODO: добавить специфичные поля японской анкеты
        return $this->commonFormSteps();
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'tourism', 'business', 'transit' => 5,
            'study', 'cultural'              => 14,
            'work'                           => 30,
            default                          => 7,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return null; // Япония не имеет онлайн-подачи, только через посольство
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Садыка Азимова, 9, Ташкент 100047, Узбекистан',
            'phone'         => '+998 71 120 80 60',
            'email'         => 'info@ts.mofa.go.jp',
            'website'       => 'https://www.uz.emb-japan.go.jp',
            'working_hours' => 'Пн-Пт: 09:00-12:30, 14:00-17:45',
        ];
    }
}
