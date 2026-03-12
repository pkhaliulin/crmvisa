<?php

namespace App\Modules\Case\Engines;

class SpainEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'ES';
    }

    public function getCountryName(): string
    {
        return 'Испания';
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
            'description' => 'Подтверждение проживания на территории Испании',
        ];
        $docs[] = [
            'name'        => 'Бронь авиабилетов',
            'name_uz'     => 'Aviabilet bron',
            'requirement' => 'required',
            'description' => 'Туда и обратно',
        ];

        if ($visaType === 'business') {
            $docs[] = [
                'name'        => 'Приглашение от испанской компании',
                'name_uz'     => 'Ispaniya kompaniyasidan taklif',
                'requirement' => 'required',
                'description' => 'Carta de invitación с указанием цели и сроков',
            ];
        }

        if ($visaType === 'study') {
            $docs[] = [
                'name'        => 'Подтверждение зачисления',
                'name_uz'     => 'Qabul tasdig\'i',
                'requirement' => 'required',
                'description' => 'Matrícula от учебного заведения',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        // TODO: добавить специфичные поля для испанской анкеты
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
        return 'https://www.exteriores.gob.es/Consulados/tashkent'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => null, // TODO: уточнить адрес консульства Испании в Ташкенте
            'phone'         => null,
            'email'         => null,
            'website'       => 'https://www.exteriores.gob.es/Consulados/tashkent',
            'working_hours' => null,
        ];
    }
}
