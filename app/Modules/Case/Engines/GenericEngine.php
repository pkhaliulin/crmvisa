<?php

namespace App\Modules\Case\Engines;

/**
 * Fallback engine для стран без специфической реализации.
 * Предоставляет базовый набор типов виз и документов.
 */
class GenericEngine extends BaseCountryEngine
{
    public function __construct(
        private readonly string $countryCode,
        private readonly string $countryName = '',
    ) {}

    public function getCountryCode(): string
    {
        return strtoupper($this->countryCode);
    }

    public function getCountryName(): string
    {
        return $this->countryName ?: $this->countryCode;
    }

    public function isSchengen(): bool
    {
        return false;
    }

    public function getStatus(): string
    {
        return 'generic';
    }

    public function getVisaTypes(): array
    {
        return [
            ['code' => 'tourism',  'label' => 'Туристическая', 'label_uz' => 'Turistik',       'schengen' => false],
            ['code' => 'business', 'label' => 'Деловая',       'label_uz' => 'Ishbilarmonlik',  'schengen' => false],
            ['code' => 'work',     'label' => 'Рабочая',       'label_uz' => 'Ish',             'schengen' => false],
            ['code' => 'study',    'label' => 'Учебная',       'label_uz' => 'O\'qish',         'schengen' => false],
        ];
    }

    public function getRequiredDocuments(string $visaType): array
    {
        return $this->commonDocuments();
    }

    public function getFormSteps(string $visaType): array
    {
        return $this->commonFormSteps();
    }

    public function getEstimatedDays(string $visaType): int
    {
        return 30; // TODO: integrate with real embassy API
    }
}
