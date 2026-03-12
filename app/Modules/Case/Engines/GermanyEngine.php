<?php

namespace App\Modules\Case\Engines;

class GermanyEngine extends BaseCountryEngine
{
    public function getCountryCode(): string
    {
        return 'DE';
    }

    public function getCountryName(): string
    {
        return 'Германия';
    }

    public function isSchengen(): bool
    {
        return true;
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'tourism',    'label' => 'Туристическая (C)',  'label_uz' => 'Turistik (C)',       'schengen' => true],
            ['code' => 'business',   'label' => 'Деловая (C)',        'label_uz' => 'Ishbilarmonlik (C)',  'schengen' => true],
            ['code' => 'study',      'label' => 'Учебная (D)',        'label_uz' => 'O\'qish (D)',         'schengen' => true],
            ['code' => 'work',       'label' => 'Рабочая (D)',        'label_uz' => 'Ish (D)',             'schengen' => true],
            ['code' => 'job_seeker', 'label' => 'Поиск работы (D)',   'label_uz' => 'Ish qidirish (D)',    'schengen' => true],
            ['code' => 'family',     'label' => 'Воссоединение семьи','label_uz' => 'Oila birlashishi',    'schengen' => true],
            ['code' => 'transit',    'label' => 'Транзитная',         'label_uz' => 'Tranzit',             'schengen' => true],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        $docs = $this->commonDocuments();

        $docs[] = [
            'name'        => 'Бронь отеля / приглашение (Verpflichtungserklärung)',
            'name_uz'     => 'Mehmonxona bron / taklif (Verpflichtungserklärung)',
            'requirement' => 'required',
            'description' => 'Подтверждение проживания или Verpflichtungserklärung от приглашающей стороны',
        ];
        $docs[] = [
            'name'        => 'Бронь авиабилетов',
            'name_uz'     => 'Aviabilet bron',
            'requirement' => 'required',
            'description' => 'Туда и обратно',
        ];

        if ($visaType === 'business') {
            $docs[] = [
                'name'        => 'Приглашение от немецкой компании',
                'name_uz'     => 'Germaniya kompaniyasidan taklif',
                'requirement' => 'required',
                'description' => 'Einladungsschreiben с печатью и подписью',
            ];
        }

        if ($visaType === 'study') {
            $docs[] = [
                'name'        => 'Подтверждение зачисления (Zulassungsbescheid)',
                'name_uz'     => 'Qabul tasdig\'i (Zulassungsbescheid)',
                'requirement' => 'required',
                'description' => 'Zulassungsbescheid или условное зачисление от университета',
            ];
            $docs[] = [
                'name'        => 'Блокированный счёт (Sperrkonto)',
                'name_uz'     => 'Bloklangan hisob (Sperrkonto)',
                'requirement' => 'required',
                'description' => 'Подтверждение финансовой состоятельности (11 208 EUR/год)',
            ];
        }

        if ($visaType === 'job_seeker') {
            $docs[] = [
                'name'        => 'Диплом с признанием (Anabin)',
                'name_uz'     => 'Diplom tan olinishi (Anabin)',
                'requirement' => 'required',
                'description' => 'Диплом, признанный в Германии (проверка через Anabin)',
            ];
            $docs[] = [
                'name'        => 'Мотивационное письмо',
                'name_uz'     => 'Motivatsion xat',
                'requirement' => 'required',
                'description' => 'План поиска работы в Германии',
            ];
        }

        // TODO: integrate with real embassy API

        return $docs;
    }

    public function getFormSteps(string $visaType): array
    {
        // TODO: добавить специфичные поля для немецкой анкеты (VIDEX)
        return $this->commonFormSteps();
    }

    public function getEstimatedDays(string $visaType): int
    {
        return match ($visaType) {
            'tourism', 'business', 'transit' => 15,
            'study'                          => 30,
            'work', 'job_seeker'             => 60,
            'family'                         => 90,
            default                          => 15,
        };
    }

    public function getExternalSubmissionUrl(): ?string
    {
        return 'https://service2.diplo.de/rktermin/extern/choose_re498.do'; // TODO: integrate with real embassy API
    }

    public function getEmbassyInfo(): array
    {
        return [
            'address'       => 'ул. Шарафа Рашидова, 15, Ташкент 100017, Узбекистан',
            'phone'         => '+998 71 120 84 40',
            'email'         => 'info@taschkent.diplo.de',
            'website'       => 'https://taschkent.diplo.de',
            'working_hours' => 'Пн-Пт: 08:00-11:00 (приём документов)',
        ];
    }
}
