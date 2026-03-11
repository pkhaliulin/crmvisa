<?php

namespace App\Modules\Case\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Models\VisaFormFieldMapping;
use App\Modules\Client\Models\Client;

class FranceFormService
{
    /**
     * Получить все поля анкеты по шагам с текущими значениями.
     */
    public static function getFormFields(VisaCase $case): array
    {
        $rule = $case->rule;
        if (! $rule) {
            return [];
        }

        $mappings = $rule->fieldMappings()
            ->where('is_active', true)
            ->orderBy('step_number')
            ->orderBy('display_order')
            ->get();

        $formData = $case->form_data ?? [];
        $steps = [];

        foreach ($mappings as $mapping) {
            $stepNum = $mapping->step_number;
            if (! isset($steps[$stepNum])) {
                $steps[$stepNum] = [
                    'step'   => $stepNum,
                    'title'  => $mapping->step_title,
                    'fields' => [],
                    'filled' => 0,
                    'total'  => 0,
                ];
            }

            $value = $formData[$mapping->field_key] ?? null;
            $steps[$stepNum]['fields'][] = [
                'key'        => $mapping->field_key,
                'label'      => $mapping->field_label,
                'type'       => $mapping->field_type,
                'options'    => $mapping->options,
                'value'      => $value,
                'required'   => $mapping->is_required,
                'help_text'  => $mapping->help_text,
                'source'     => $mapping->mapping_source,
                'transform'  => $mapping->transform_rule,
                'validation' => $mapping->validation_rules,
            ];

            $steps[$stepNum]['total']++;
            if (! empty($value)) {
                $steps[$stepNum]['filled']++;
            }
        }

        return array_values($steps);
    }

    /**
     * Автозаполнение полей из данных клиента и заявки.
     */
    public static function prefillFromClient(VisaCase $case): array
    {
        $rule = $case->rule;
        if (! $rule) {
            return [];
        }

        $case->loadMissing('client');
        $client = $case->client;
        if (! $client) {
            return [];
        }

        $mappings = $rule->fieldMappings()
            ->where('is_active', true)
            ->whereNotNull('mapping_source')
            ->get();

        $prefilled = [];

        foreach ($mappings as $mapping) {
            $value = static::resolveFieldValue($mapping, $case, $client);
            if ($value !== null && $value !== '') {
                $value = static::applyTransform($value, $mapping->transform_rule);
                $prefilled[$mapping->field_key] = $value;
            }
        }

        return $prefilled;
    }

    /**
     * Сохранить данные шага анкеты.
     */
    public static function saveFormStep(VisaCase $case, int $step, array $data): void
    {
        $formData = $case->form_data ?? [];

        foreach ($data as $key => $value) {
            $formData[$key] = $value;
        }

        $case->update(['form_data' => $formData]);
        VisaCaseEngineService::refreshCaseReadiness($case);
    }

    /**
     * Применить prefill ко всем полям разом.
     */
    public static function applyPrefill(VisaCase $case): array
    {
        $prefilled = static::prefillFromClient($case);

        if (! empty($prefilled)) {
            $formData = array_merge($case->form_data ?? [], $prefilled);
            $case->update(['form_data' => $formData]);
            VisaCaseEngineService::refreshCaseReadiness($case);
        }

        return $prefilled;
    }

    /**
     * Прогресс заполнения по шагам.
     */
    public static function getFormProgress(VisaCase $case): array
    {
        $steps = static::getFormFields($case);
        $totalFilled = 0;
        $totalRequired = 0;

        foreach ($steps as &$step) {
            $requiredInStep = collect($step['fields'])->where('required', true)->count();
            $filledRequired = collect($step['fields'])->where('required', true)->filter(fn ($f) => ! empty($f['value']))->count();
            $step['required_count']  = $requiredInStep;
            $step['required_filled'] = $filledRequired;
            $step['complete']        = $requiredInStep > 0 ? $filledRequired >= $requiredInStep : true;

            $totalFilled += $filledRequired;
            $totalRequired += $requiredInStep;
        }

        return [
            'steps'          => $steps,
            'total_required' => $totalRequired,
            'total_filled'   => $totalFilled,
            'percent'        => $totalRequired > 0 ? (int) round(($totalFilled / $totalRequired) * 100) : 100,
        ];
    }

    /**
     * Разрешить значение поля из source entity.
     */
    private static function resolveFieldValue(VisaFormFieldMapping $mapping, VisaCase $case, Client $client): mixed
    {
        $source = $mapping->mapping_source;
        if (! $source) return null;

        [$entity, $field] = explode('.', $source, 2) + [null, null];
        if (! $entity || ! $field) return null;

        return match ($entity) {
            'client' => $client->{$field} ?? null,
            'case'   => $case->{$field} ?? null,
            default  => null,
        };
    }

    /**
     * Применить трансформацию к значению.
     */
    private static function applyTransform(mixed $value, ?string $rule): mixed
    {
        if (! $rule || $value === null) return $value;

        return match ($rule) {
            'uppercase'    => mb_strtoupper((string) $value),
            'date_dmy'     => $value instanceof \DateTimeInterface
                ? $value->format('d/m/Y')
                : (is_string($value) ? (rescue(fn () => \Carbon\Carbon::parse($value)->format('d/m/Y'), $value)) : $value),
            'country_name' => static::countryCodeToName((string) $value),
            default        => $value,
        };
    }

    /**
     * ISO country code -> name (для France-Visas).
     */
    private static function countryCodeToName(string $code): string
    {
        $map = [
            'UZ' => 'Uzbekistan', 'RU' => 'Russia', 'KZ' => 'Kazakhstan',
            'FR' => 'France', 'DE' => 'Germany', 'IT' => 'Italy',
            'ES' => 'Spain', 'GB' => 'United Kingdom', 'US' => 'United States',
            'TR' => 'Turkey', 'AE' => 'United Arab Emirates', 'TJ' => 'Tajikistan',
            'KG' => 'Kyrgyzstan', 'TM' => 'Turkmenistan', 'CN' => 'China',
            'JP' => 'Japan', 'KR' => 'South Korea', 'IN' => 'India',
        ];

        return $map[strtoupper($code)] ?? $code;
    }
}
