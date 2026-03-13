<?php

namespace App\Modules\Case\Services;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Models\VisaFormFieldMapping;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Models\CaseChecklist;

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
     * Автозаполнение полей из данных клиента, заявки и AI-анализа документов.
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
            ->get();

        // Собрать данные из AI-анализа документов
        $aiData = static::collectAiExtractedData($case);

        $prefilled = [];

        foreach ($mappings as $mapping) {
            // 1) Попробовать из client/case (mapping_source)
            $value = null;
            if ($mapping->mapping_source) {
                $value = static::resolveFieldValue($mapping, $case, $client);
            }

            // 2) Если пусто — попробовать из AI-анализа
            if (($value === null || $value === '') && isset($aiData[$mapping->field_key])) {
                $value = $aiData[$mapping->field_key];
            }

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
     * Собрать данные из AI-анализа документов и преобразовать в form field keys.
     */
    private static function collectAiExtractedData(VisaCase $case): array
    {
        $checklists = CaseChecklist::where('case_id', $case->id)
            ->whereNotNull('ai_analysis')
            ->whereNull('family_member_id')
            ->with('countryRequirement.template:id,slug')
            ->get();

        if ($checklists->isEmpty()) {
            return [];
        }

        // Маппинг: document_slug => [ai_field => form_field_key]
        $aiFieldMap = [
            'foreign_passport' => [
                'surname'           => 'surname',
                'given_names'       => 'first_name',
                'date_of_birth'     => 'birth_date',
                'nationality'       => 'nationality',
                'sex'               => 'sex',
                'passport_number'   => 'passport_number',
                'issue_date'        => 'passport_issue_date',
                'expiry_date'       => 'passport_expiry_date',
                'issuing_authority' => 'passport_issued_by',
            ],
            'internal_passport' => [
                'place_of_birth'       => 'birth_place',
                'marital_status'       => 'marital_status',
                'registration_address' => 'form_filler_address',
            ],
            'marriage_certificate' => [
                '_implies_married' => 'marital_status',
            ],
            'income_certificate' => [
                'position'      => 'occupation',
                'employer_name' => 'employer_name',
            ],
            'old_passports' => [
                'schengen_visas' => 'has_previous_schengen',
            ],
        ];

        $result = [];

        foreach ($checklists as $checklist) {
            $analysis = $checklist->ai_analysis;
            if (! is_array($analysis)) {
                continue;
            }

            $extracted = $analysis['extracted_data'] ?? $analysis['extracted'] ?? [];
            if (empty($extracted)) {
                continue;
            }

            // Определить slug документа через цепочку country_requirement → template
            $slug = $checklist->countryRequirement?->template?->slug;
            if (! $slug) {
                continue;
            }

            $fieldMap = $aiFieldMap[$slug] ?? null;
            if (! $fieldMap) {
                continue;
            }

            // Специальная обработка: свидетельство о браке = married
            if ($slug === 'marriage_certificate' && ! empty($extracted)) {
                if (! isset($result['marital_status'])) {
                    $result['marital_status'] = 'married';
                }
                continue;
            }

            // Специальная обработка: old_passports schengen_visas > 0 => has_previous_schengen = yes
            if ($slug === 'old_passports' && isset($extracted['schengen_visas'])) {
                $result['has_previous_schengen'] = ((int) $extracted['schengen_visas']) > 0 ? 'yes' : 'no';
                continue;
            }

            foreach ($fieldMap as $aiField => $formField) {
                if (str_starts_with($aiField, '_')) {
                    continue;
                }
                $val = $extracted[$aiField] ?? null;
                if ($val !== null && $val !== '' && ! isset($result[$formField])) {
                    $result[$formField] = $val;
                }
            }
        }

        // Национальность из AI приходит как "UZBEKISTAN" — нужно конвертировать в код
        if (isset($result['nationality'])) {
            $result['nationality'] = static::nationalityToCode($result['nationality']);
            if (! isset($result['nationality_at_birth'])) {
                $result['nationality_at_birth'] = $result['nationality'];
            }
        }

        // birth_country из nationality
        if (! isset($result['birth_country']) && isset($result['nationality'])) {
            $result['birth_country'] = $result['nationality'];
        }

        // passport_issue_country из nationality
        if (! isset($result['passport_issue_country']) && isset($result['nationality'])) {
            $result['passport_issue_country'] = $result['nationality'];
        }

        return $result;
    }

    /**
     * Полное имя национальности -> ISO country code.
     */
    private static function nationalityToCode(string $name): string
    {
        $map = [
            'UZBEKISTAN' => 'UZ', 'RUSSIA' => 'RU', 'KAZAKHSTAN' => 'KZ',
            'FRANCE' => 'FR', 'GERMANY' => 'DE', 'ITALY' => 'IT',
            'SPAIN' => 'ES', 'UNITED KINGDOM' => 'GB', 'UNITED STATES' => 'US',
            'TURKEY' => 'TR', 'UNITED ARAB EMIRATES' => 'AE', 'TAJIKISTAN' => 'TJ',
            'KYRGYZSTAN' => 'KG', 'TURKMENISTAN' => 'TM', 'CHINA' => 'CN',
            'JAPAN' => 'JP', 'SOUTH KOREA' => 'KR', 'INDIA' => 'IN',
            'UZBEK' => 'UZ', 'RUSSIAN' => 'RU', 'KAZAKH' => 'KZ',
            'FRENCH' => 'FR', 'GERMAN' => 'DE', 'ITALIAN' => 'IT',
            'SPANISH' => 'ES', 'BRITISH' => 'GB', 'AMERICAN' => 'US',
            'TURKISH' => 'TR', 'TAJIK' => 'TJ', 'KYRGYZ' => 'KG',
            'TURKMEN' => 'TM', 'CHINESE' => 'CN', 'JAPANESE' => 'JP',
            'KOREAN' => 'KR', 'INDIAN' => 'IN',
        ];

        return $map[mb_strtoupper(trim($name))] ?? $name;
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
