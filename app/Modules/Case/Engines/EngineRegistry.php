<?php

namespace App\Modules\Case\Engines;

class EngineRegistry
{
    /**
     * Получить engine по коду страны.
     */
    public static function resolve(string $countryCode): CountryEngineInterface
    {
        return match (strtoupper($countryCode)) {
            'FR' => new FranceEngine(),
            'IT' => new ItalyEngine(),
            'ES' => new SpainEngine(),
            'DE' => new GermanyEngine(),
            'GB' => new UKEngine(),
            'US' => new USAEngine(),
            'CA' => new CanadaEngine(),
            'JP' => new JapanEngine(),
            'KR' => new SouthKoreaEngine(),
            'AE' => new UAEEngine(),
            'TR' => new TurkeyEngine(),
            default => new GenericEngine($countryCode),
        };
    }

    /**
     * Список поддерживаемых стран с engine.
     *
     * @return array<string, array{code: string, name: string, schengen: bool, status: string}>
     */
    public static function supportedCountries(): array
    {
        $engines = [
            new FranceEngine(),
            new ItalyEngine(),
            new SpainEngine(),
            new GermanyEngine(),
            new UKEngine(),
            new USAEngine(),
            new CanadaEngine(),
            new JapanEngine(),
            new SouthKoreaEngine(),
            new UAEEngine(),
            new TurkeyEngine(),
        ];

        $countries = [];
        foreach ($engines as $engine) {
            $countries[] = [
                'code'      => $engine->getCountryCode(),
                'name'      => $engine->getCountryName(),
                'schengen'  => $engine->isSchengen(),
                'status'    => $engine->getStatus(),
                'visa_types_count' => count($engine->getVisaTypes()),
            ];
        }

        return $countries;
    }

    /**
     * Проверить, есть ли специфический engine для страны (не generic).
     */
    public static function hasEngine(string $countryCode): bool
    {
        $engine = static::resolve($countryCode);

        return $engine->getStatus() !== 'generic';
    }

    /**
     * Список кодов стран с engine.
     *
     * @return array<string>
     */
    public static function supportedCountryCodes(): array
    {
        return ['FR', 'IT', 'ES', 'DE', 'GB', 'US', 'CA', 'JP', 'KR', 'AE', 'TR'];
    }
}
