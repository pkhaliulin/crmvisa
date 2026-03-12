<?php

namespace App\Modules\Case\Engines;

class ItalyEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'IT';
    }

    public function getCountryName(): string
    {
        return 'Италия';
    }

    public function isSchengen(): bool
    {
        return true;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'tourism',  'label' => 'Туристическая (C)', 'label_uz' => 'Turistik (C)',       'schengen' => true],
            ['code' => 'business', 'label' => 'Деловая (C)',       'label_uz' => 'Ishbilarmonlik (C)',  'schengen' => true],
            ['code' => 'study',    'label' => 'Учебная (D)',       'label_uz' => 'O\'qish (D)',         'schengen' => true],
            ['code' => 'work',     'label' => 'Рабочая (D)',       'label_uz' => 'Ish (D)',             'schengen' => true],
            ['code' => 'family',   'label' => 'Семейная',          'label_uz' => 'Oilaviy',             'schengen' => true],
            ['code' => 'transit',  'label' => 'Транзитная',        'label_uz' => 'Tranzit',             'schengen' => true],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        $docs = $this->commonDocuments();

        $docs[] = [
            'name'        => 'Бронь отеля / приглашение',
            'name_uz'     => 'Mehmonxona bron / taklif',
            'requirement' => 'required',
            'description' => 'Подтверждение проживания на весь срок',
        ];
        $docs[] = [
            'name'        => 'Бронь авиабилетов',
            'name_uz'     => 'Aviabilet bron',
            'requirement' => 'required',
            'description' => 'Туда и обратно',
        ];

        if ($visaType === 'business') {
            $docs[] = [
                'name'        => 'Приглашение от итальянской компании',
                'name_uz'     => 'Italiya kompaniyasidan taklif',
                'requirement' => 'required',
                'description' => 'С указанием цели визита и данных приглашающей стороны',
            ];
        }

        if ($visaType === 'study') {
            $docs[] = [
                'name'        => 'Подтверждение зачисления в учебное заведение',
                'name_uz'     => 'O\'quv muassasasiga qabul tasdig\'i',
                'requirement' => 'required',
                'description' => 'Lettera di ammissione от университета',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        // TODO: добавить специфичные поля для итальянской анкеты
        return $this->commonFormSteps();
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'tourism', 'business', 'transit' => 15,
            'study'                          => 30,
            'work'                           => 60,
            default                          => 15,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://prenotami.esteri.it'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Юсуфа Хос Хожиба, 40, Ташкент, Узбекистан',
            'phone'         => '+998 71 120 60 42',
            'email'         => 'ambasciata.tashkent@esteri.it',
            'website'       => 'https://ambtashkent.esteri.it',
            'working_hours' => 'Пн-Пт: 09:00-12:00',
        ];
    }
}
