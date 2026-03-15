<?php

namespace App\Modules\Scoring\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Единый движок скоринга (SSOT).
 *
 * Работает с простым массивом данных — не привязан к модели.
 * Используется и в CRM (агентская часть), и в клиентском портале.
 *
 * 4 блока:
 *   F  — Финансы (доход, занятость, банк)
 *   T  — Привязанность к родине (семья, дети, имущество, стаж)
 *   V  — Визовая история (визы, отказы, overstay)
 *   P  — Профиль (возраст, образование)
 *
 * Формула:
 *   profile_base = sum(block_score * weight) / 100
 *   sensitivity  = 0.10 + 0.30 * (1 - profile_base / 100)
 *   country_bonus = (100 - difficulty) * sensitivity
 *   visa_adj = (visa_type_score - 60) * 0.08
 *   raw = profile_base + country_bonus + visa_adj
 *   score = clamp(round(raw * red_flag_multiplier), 5, 100)
 */
class UnifiedScoringEngine
{
    /**
     * Веса по умолчанию (F:30, T:25, V:30, P:15 = 100).
     */
    private const DEFAULT_WEIGHTS = [
        'F' => 30,
        'T' => 25,
        'V' => 30,
        'P' => 15,
    ];

    // =========================================================================
    // Блок F — Финансы (0-100)
    // =========================================================================

    public function calcFinances(array $d): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];

        // Определяем доступность расширенных данных (CRM vs публичный портал)
        $hasExtendedData = isset($d['income_type']) || isset($d['bank_history_months']);

        // --- Тип занятости ---
        $empType = $d['employment_type'] ?? null;
        $empScore = match ($empType) {
            'government'     => 45,
            'business_owner' => 42,
            'employed'       => 38,
            'private'        => 38,
            'self_employed'  => 28,
            'retired'        => 22,
            'student'        => 12,
            'unemployed'     => 0,
            default          => 0,
        };
        $raw += $empScore;

        if ($empType === 'unemployed' || !$empType) {
            $flags[] = 'no_employment';
            $recs[] = ['type' => 'F', 'priority' => 'high', 'text' => 'employment_needed'];
        }

        // --- Ежемесячный доход ---
        $income = (int) ($d['monthly_income_usd'] ?? $d['monthly_income'] ?? 0);
        $incomeScore = match (true) {
            $income >= 5000 => 50,
            $income >= 3000 => 40,
            $income >= 1500 => 30,
            $income >= 800  => 18,
            $income >= 400  => 10,
            $income > 0     => 4,
            default         => 0,
        };
        $raw += $incomeScore;

        if ($income < 500 && $income > 0) {
            $flags[] = 'low_income';
            $recs[] = ['type' => 'F', 'priority' => 'high', 'text' => 'income_low'];
        }
        if ($income === 0) {
            $recs[] = ['type' => 'F', 'priority' => 'high', 'text' => 'income_not_specified'];
        }

        if ($hasExtendedData) {
            // --- Официальный доход (CRM) ---
            $incomeType = $d['income_type'] ?? null;
            if ($incomeType === 'official') {
                $raw += 5;
            } elseif ($empType && $empType !== 'unemployed' && $empType !== 'student') {
                $recs[] = ['type' => 'F', 'priority' => 'medium', 'text' => 'official_income_helps'];
            }

            // --- Банковская история (CRM) ---
            $bankMonths = (int) ($d['bank_history_months'] ?? 0);
            if ($bankMonths >= 6)      $raw += 5;
            elseif ($bankMonths >= 3)  $raw += 3;
            else $recs[] = ['type' => 'F', 'priority' => 'medium', 'text' => 'bank_statement_helps'];

            // --- Стабильность баланса (CRM) ---
            if ($d['bank_balance_stable'] ?? false) $raw += 2;

            // --- Депозиты и инвестиции (CRM) ---
            if ($d['has_fixed_deposit'] ?? false) $raw += 1;
            if ($d['has_investments'] ?? false)   $raw += 1;
        }

        // maxRaw зависит от доступных данных:
        // Публичный: занятость(45) + доход(50) = 95 => нормализуем на 95
        // CRM: +5+5+2+1+1 = 109 => нормализуем на 109
        $maxRaw = $hasExtendedData ? 109 : 95;

        $score = (int) round(min($raw / $maxRaw * 100, 100));

        return compact('score', 'flags', 'recs');
    }

    // =========================================================================
    // Блок T — Привязанность к родине (0-100)
    // =========================================================================

    public function calcTies(array $d): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];
        $maxRaw = 100;

        // --- Семейное положение (0-20) ---
        $marital = $d['marital_status'] ?? null;
        $raw += match ($marital) {
            'married'  => 20,
            'widowed'  => 10,
            'divorced' => 8,
            'single'   => 5,
            default    => 0,
        };

        // --- Работающий супруг (0-5) ---
        if ($marital === 'married' && ($d['spouse_employed'] ?? false)) {
            $raw += 5;
        }

        // --- Дети (0-20) ---
        $hasChildren = $d['has_children'] ?? false;
        $childrenCount = (int) ($d['children_count'] ?? 0);
        $childrenStayHome = $d['children_staying_home'] ?? $hasChildren; // по умолчанию = дома

        if ($hasChildren || $childrenCount > 0) {
            if ($childrenStayHome) {
                $raw += min($childrenCount * 10, 20);
            } else {
                $raw += 5;
                $flags[] = 'children_not_home';
            }
        }

        // --- Имущество (0-25) ---
        if ($d['has_property'] ?? $d['has_real_estate'] ?? false) {
            $raw += 25;
        } else {
            $recs[] = ['type' => 'T', 'priority' => 'medium', 'text' => 'property_helps'];
        }

        // --- Автомобиль (0-8) ---
        if ($d['has_car'] ?? false) {
            $raw += 8;
        }

        // --- Бизнес (0-12) ---
        if ($d['has_business'] ?? false) {
            $raw += 12;
        }

        // --- Стаж работы (0-10) ---
        $years = (int) ($d['employed_years'] ?? $d['years_at_current_job'] ?? $d['total_work_experience'] ?? 0);
        $raw += match (true) {
            $years >= 5  => 10,
            $years >= 3  => 7,
            $years >= 1  => 4,
            default      => 0,
        };

        if ($raw < 20) {
            $flags[] = 'weak_ties';
        }

        $score = (int) round(min($raw / $maxRaw * 100, 100));

        return compact('score', 'flags', 'recs');
    }

    // =========================================================================
    // Блок V — Визовая история (0-100)
    // =========================================================================

    public function calcVisaHistory(array $d): array
    {
        $raw   = 50; // Базовая нейтральная оценка (нет истории — не наказание)
        $flags = [];
        $recs  = [];

        // --- Существующие визы (бонусы) ---
        if ($d['has_us_visa'] ?? false)       $raw += 20;
        if ($d['has_schengen_visa'] ?? false)  $raw += 15;
        if ($d['has_uk_visa'] ?? false)        $raw += 12;

        // --- Количество полученных виз ---
        $visasObtained = (int) ($d['visas_obtained_count'] ?? 0);
        if ($visasObtained >= 5)     $raw += 15;
        elseif ($visasObtained >= 3) $raw += 10;
        elseif ($visasObtained >= 1) $raw += 5;

        // --- Отказы (штрафы) ---
        $refusals = (int) ($d['refusals_count'] ?? $d['previous_refusals'] ?? 0);
        if ($refusals >= 3) {
            $raw -= 40;
            $flags[] = 'multiple_refusals';
        } elseif ($refusals === 2) {
            $raw -= 25;
            $flags[] = 'two_refusals';
        } elseif ($refusals === 1) {
            $raw -= 12;
            $flags[] = 'one_refusal';
            $recs[] = ['type' => 'V', 'priority' => 'high', 'text' => 'refusal_docs_needed'];
        }

        // --- Overstay (штраф) ---
        if ($d['has_overstay'] ?? $d['had_overstay'] ?? false) {
            $raw -= 25;
            $flags[] = 'overstay';
        }

        if (!($d['has_us_visa'] ?? false) && !($d['has_schengen_visa'] ?? false) && !($d['has_uk_visa'] ?? false) && $visasObtained === 0) {
            $recs[] = ['type' => 'V', 'priority' => 'medium', 'text' => 'visa_history_empty'];
        }

        $score = max(0, min(100, $raw));

        return compact('score', 'flags', 'recs');
    }

    // =========================================================================
    // Блок P — Профиль (0-100)
    // =========================================================================

    public function calcProfile(array $d): array
    {
        $raw   = 0;
        $flags = [];
        $recs  = [];
        $maxRaw = 100;
        $isBlocked = false;

        // --- Судимость = блокировка ---
        if ($d['has_criminal_record'] ?? false) {
            return [
                'score'      => 0,
                'flags'      => ['criminal_record'],
                'recs'       => [['type' => 'P', 'priority' => 'high', 'text' => 'criminal_record_block']],
                'is_blocked' => true,
            ];
        }

        // --- Образование (0-40) ---
        $edu = $d['education_level'] ?? null;
        $raw += match ($edu) {
            'phd'        => 40,
            'master'     => 35,
            'bachelor'   => 28,
            'vocational' => 18,
            'secondary'  => 10,
            'none'       => 0,
            default      => 5, // не указано
        };

        if (!$edu || $edu === 'none') {
            $recs[] = ['type' => 'P', 'priority' => 'low', 'text' => 'education_helps'];
        }

        // --- Возраст (0-60) ---
        $age = $this->resolveAge($d);
        if ($age > 0) {
            $raw += match (true) {
                $age >= 30 && $age <= 50 => 60, // оптимальный: семья, карьера, стабильность
                $age >= 25 && $age < 30  => 50, // молодой специалист
                $age > 50 && $age <= 60  => 45, // предпенсионный
                $age >= 18 && $age < 25  => 35, // студент / начало карьеры
                $age > 60                => 30, // пенсионер
                default                  => 25,
            };
        } else {
            $raw += 25; // не указан возраст — нейтральная оценка
        }

        $score = (int) round(min($raw / $maxRaw * 100, 100));

        return compact('score', 'flags', 'recs', 'isBlocked');
    }

    // =========================================================================
    // Красные флаги (множители)
    // =========================================================================

    public function calcRedFlagMultiplier(array $d): float
    {
        $multiplier = 1.0;

        // Судимость — полная блокировка
        if ($d['has_criminal_record'] ?? false) {
            return 0.0;
        }

        // Депортация — серьёзнейший негативный фактор
        if ($d['had_deportation'] ?? false) {
            $multiplier = min($multiplier, 0.5);
        }

        // 3+ отказа за последние 3 года
        $refusals = (int) ($d['refusals_count'] ?? $d['previous_refusals'] ?? 0);
        $lastRefusalYear = (int) ($d['last_refusal_year'] ?? 0);
        $currentYear = (int) date('Y');
        if ($refusals >= 3 && $lastRefusalYear >= ($currentYear - 3)) {
            $multiplier = min($multiplier, 0.6);
        }

        // Overstay
        if ($d['has_overstay'] ?? $d['had_overstay'] ?? false) {
            $multiplier = min($multiplier, 0.7);
        }

        return $multiplier;
    }

    // =========================================================================
    // Базовый скоринг профиля (без привязки к стране)
    // =========================================================================

    /**
     * Рассчитать базовый скоринг профиля.
     * Используется на странице «Ваш скоринг» в клиентском портале
     * и в CRM для базовой оценки клиента.
     */
    public function scoreProfile(array $data): array
    {
        $F = $this->calcFinances($data);
        $T = $this->calcTies($data);
        $V = $this->calcVisaHistory($data);
        $P = $this->calcProfile($data);

        $isBlocked = $P['isBlocked'] ?? $P['is_blocked'] ?? false;
        $weights = self::DEFAULT_WEIGHTS;

        $blocks = [
            'F' => $F['score'],
            'T' => $T['score'],
            'V' => $V['score'],
            'P' => $P['score'],
        ];

        // Взвешенная сумма
        $profileBase = $isBlocked ? 0 : $this->applyWeights($blocks, $weights);

        // Красные флаги
        $multiplier = $this->calcRedFlagMultiplier($data);
        $finalScore = (int) round($profileBase * $multiplier);
        $finalScore = max(5, min(100, $finalScore));

        // Собрать все флаги и рекомендации
        $allFlags = array_merge($F['flags'], $T['flags'], $V['flags'], $P['flags']);
        $allRecs  = array_merge($F['recs'],  $T['recs'],  $V['recs'],  $P['recs']);

        // Сортировать рекомендации по приоритету
        $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($allRecs, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 9) - ($priorityOrder[$b['priority']] ?? 9));

        return [
            'score'               => $finalScore,
            'blocks'              => $blocks,
            'flags'               => array_values(array_unique($allFlags)),
            'recommendations'     => array_slice($allRecs, 0, 6),
            'red_flag_multiplier' => $multiplier,
            'is_blocked'          => $isBlocked,
            'weights'             => $weights,
        ];
    }

    // =========================================================================
    // Скоринг по конкретной стране
    // =========================================================================

    /**
     * Рассчитать скоринг для конкретной страны + типа визы.
     */
    public function scoreForCountry(array $data, string $countryCode, string $visaType = 'tourist'): array
    {
        $F = $this->calcFinances($data);
        $T = $this->calcTies($data);
        $V = $this->calcVisaHistory($data);
        $P = $this->calcProfile($data);

        $isBlocked = $P['isBlocked'] ?? $P['is_blocked'] ?? false;

        // Загрузить веса для страны (или дефолтные)
        $weights = $this->loadCountryWeights($countryCode);

        $blocks = [
            'F' => $F['score'],
            'T' => $T['score'],
            'V' => $V['score'],
            'P' => $P['score'],
        ];

        // Шаг 1: Взвешенная база профиля
        $profileBase = $isBlocked ? 0 : $this->applyWeights($blocks, $weights);

        // Шаг 2: Сложность страны (нелинейная корректировка)
        $difficulty = $this->getCountryDifficulty($countryCode);
        $sensitivity = 0.10 + 0.30 * (1 - $profileBase / 100);
        $countryBonus = (100 - $difficulty) * $sensitivity;

        // Шаг 3: Тип визы
        $visaTypeScore = $this->calcVisaTypeScore($visaType);
        $visaAdj = ($visaTypeScore - 60) * 0.08;

        // Шаг 4: Итого
        $rawScore = $profileBase + $countryBonus + $visaAdj;

        // Шаг 5: Красные флаги
        $multiplier = $this->calcRedFlagMultiplier($data);
        $total = (int) round($rawScore * $multiplier);
        $total = max(5, min(100, $total));

        // Собрать все флаги и рекомендации
        $allFlags = array_merge($F['flags'], $T['flags'], $V['flags'], $P['flags']);
        $allRecs  = array_merge($F['recs'],  $T['recs'],  $V['recs'],  $P['recs']);

        $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($allRecs, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 9) - ($priorityOrder[$b['priority']] ?? 9));

        return [
            'country_code'        => $countryCode,
            'score'               => $total,
            'label'               => $this->scoreLabel($total),
            'blocks'              => $blocks,
            'weights'             => $weights,
            'breakdown'           => [
                'profile_base'   => round($profileBase, 1),
                'difficulty'     => $difficulty,
                'sensitivity'    => round($sensitivity, 3),
                'country_bonus'  => round($countryBonus, 1),
                'visa_type'      => $visaTypeScore,
                'visa_adj'       => round($visaAdj, 2),
                'raw'            => round($rawScore, 1),
            ],
            'flags'               => array_values(array_unique($allFlags)),
            'recommendations'     => array_slice($allRecs, 0, 6),
            'red_flag_multiplier' => $multiplier,
            'is_blocked'          => $isBlocked,
        ];
    }

    // =========================================================================
    // Приватные методы
    // =========================================================================

    private function applyWeights(array $blocks, array $weights): float
    {
        $total = 0.0;
        $totalWeight = 0.0;

        foreach ($blocks as $code => $score) {
            $w = $weights[$code] ?? 0;
            $total += $score * $w / 100;
            $totalWeight += $w;
        }

        if ($totalWeight > 0 && $totalWeight != 100) {
            $total = $total / $totalWeight * 100;
        }

        return round(min(max($total, 0), 100), 2);
    }

    private function loadCountryWeights(string $countryCode): array
    {
        $cacheKey = "scoring_weights_{$countryCode}";

        return Cache::remember($cacheKey, 3600, function () use ($countryCode) {
            // Сначала ищем в scoring_country_weights (новый формат 4 блока)
            $rows = DB::table('scoring_country_weights')
                ->where('country_code', $countryCode)
                ->whereIn('block_code', ['F', 'T', 'V', 'P'])
                ->pluck('weight', 'block_code');

            if ($rows->isNotEmpty()) {
                return $rows->toArray() + self::DEFAULT_WEIGHTS;
            }

            // Иначе — дефолт
            return self::DEFAULT_WEIGHTS;
        });
    }

    private function getCountryDifficulty(string $countryCode): int
    {
        $all = Cache::remember('portal_countries_difficulty', 3600, function () {
            return DB::table('portal_countries')
                ->where('is_active', true)
                ->pluck('difficulty_score', 'country_code')
                ->toArray();
        });

        return (int) ($all[$countryCode] ?? 50);
    }

    private function calcVisaTypeScore(string $visaType): int
    {
        return match ($visaType) {
            'tourist'  => 80,
            'business' => 65,
            'student'  => 55,
            'work'     => 40,
            'transit'  => 85,
            default    => 60,
        };
    }

    private function resolveAge(array $d): int
    {
        // Прямое указание возраста
        if (isset($d['age']) && $d['age'] > 0) {
            return (int) $d['age'];
        }

        // Из даты рождения
        if (!empty($d['dob'])) {
            try {
                return (int) now()->diffInYears($d['dob']);
            } catch (\Throwable) {
                return 0;
            }
        }

        return 0;
    }

    private function scoreLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'high',
            $score >= 60 => 'medium',
            $score >= 40 => 'low',
            default      => 'risk',
        };
    }
}
