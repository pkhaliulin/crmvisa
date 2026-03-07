<?php

namespace App\Modules\PublicPortal\Services;

use App\Modules\PublicPortal\Models\PublicScoreCache;
use App\Modules\PublicPortal\Models\PublicUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicScoringService
{
    private const DEFAULT_THRESHOLDS = ['high' => 80, 'medium' => 60, 'low' => 40];

    // -------------------------------------------------------------------------
    // Активная версия модели (кеш 1ч)
    // -------------------------------------------------------------------------

    private function activeModel(): ?object
    {
        return Cache::remember('scoring_model_active', 3600, function () {
            return DB::table('scoring_model_versions')
                ->where('is_active', true)
                ->first();
        });
    }

    private function thresholds(): array
    {
        $model = $this->activeModel();
        return $model
            ? (json_decode($model->thresholds, true) ?? self::DEFAULT_THRESHOLDS)
            : self::DEFAULT_THRESHOLDS;
    }

    private function redFlagRules(): array
    {
        $model = $this->activeModel();
        if (! $model) return [];
        return json_decode($model->red_flag_rules, true) ?? [];
    }

    private function modelVersion(): string
    {
        return $this->activeModel()?->version ?? '1.0';
    }

    // -------------------------------------------------------------------------
    // Данные страны из portal_countries (кеш 1ч)
    // -------------------------------------------------------------------------

    private function countryData(string $countryCode): ?object
    {
        $all = Cache::remember('portal_countries_data', 3600, function () {
            return DB::table('portal_countries')
                ->where('is_active', true)
                ->get()
                ->keyBy('country_code');
        });

        return $all[$countryCode] ?? null;
    }

    private function countryCodes(): array
    {
        return Cache::remember('portal_countries_codes', 3600, function () {
            return DB::table('portal_countries')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('country_code')
                ->toArray();
        });
    }

    // -------------------------------------------------------------------------
    // Красные флаги (блокирующие множители)
    // -------------------------------------------------------------------------

    public function applyRedFlags(PublicUser $user): float
    {
        $multiplier = 1.0;
        $rules      = $this->redFlagRules();
        $year       = (int) date('Y');

        foreach ($rules as $rule) {
            switch ($rule['condition']) {
                case 'refusals_3_in_3y':
                    $refusals = (int) ($user->refusals_count ?? 0);
                    $lastYear = (int) ($user->last_refusal_year ?? 0);
                    if ($refusals > 2 && $lastYear >= ($year - 3)) {
                        $multiplier = min($multiplier, (float) ($rule['multiplier'] ?? 0.6));
                    }
                    break;

                case 'had_overstay':
                    if ($user->had_overstay) {
                        $multiplier = min($multiplier, (float) ($rule['multiplier'] ?? 0.7));
                    }
                    break;

                case 'had_deportation':
                    if ($user->had_deportation ?? false) {
                        $multiplier = min($multiplier, (float) ($rule['multiplier'] ?? 0.5));
                    }
                    break;
            }
        }

        return $multiplier;
    }

    // -------------------------------------------------------------------------
    // Блоки расчёта профиля (0-100 каждый, не зависят от страны)
    // -------------------------------------------------------------------------

    public function calcFinances(PublicUser $user): int
    {
        $score = 0;

        $score += match ($user->employment_type) {
            'employed'       => 40,
            'business_owner' => 50,
            'self_employed'  => 35,
            'retired'        => 30,
            'student'        => 10,
            default          => 0,
        };

        $income = $user->monthly_income_usd ?? 0;
        $score += match (true) {
            $income >= 3000 => 50,
            $income >= 1500 => 35,
            $income >= 800  => 20,
            $income >= 400  => 10,
            default         => 0,
        };

        return min(100, $score);
    }

    public function calcVisaHistory(PublicUser $user): int
    {
        $score = 50;

        $visasObtained = (int) ($user->visas_obtained_count ?? 0);
        $refusals      = (int) ($user->refusals_count ?? 0);

        if ($visasObtained >= 5)     $score += 30;
        elseif ($visasObtained >= 2) $score += 20;
        elseif ($visasObtained >= 1) $score += 10;

        if ($user->has_schengen_visa) $score += 15;
        if ($user->has_us_visa)       $score += 20;

        if ($refusals >= 3)     $score -= 40;
        elseif ($refusals >= 2) $score -= 30;
        elseif ($refusals >= 1) $score -= 15;

        if ($user->had_overstay) $score -= 20;

        return max(0, min(100, $score));
    }

    public function calcSocialTies(PublicUser $user): int
    {
        $score = 0;

        if ($user->marital_status === 'married') $score += 20;
        if ($user->has_children) {
            $score += 15;
            if (($user->children_count ?? 1) >= 2) $score += 5;
        }

        if ($user->has_property) $score += 25;
        if ($user->has_car)      $score += 10;

        $empYears = (int) ($user->employed_years ?? 0);
        if ($empYears >= 5)     $score += 20;
        elseif ($empYears >= 2) $score += 10;
        elseif ($empYears >= 1) $score += 5;

        if (in_array($user->employment_type, ['employed', 'business_owner'])) $score += 5;

        $score += match ($user->education_level) {
            'phd'        => 15,
            'master'     => 12,
            'bachelor'   => 10,
            'vocational' => 6,
            'secondary'  => 3,
            default      => 0,
        };

        if ($user->dob) {
            $age = now()->diffInYears($user->dob);
            if ($age >= 30 && $age <= 55) $score += 15;
            elseif ($age >= 25)           $score += 10;
            elseif ($age >= 18)           $score += 5;
            elseif ($age > 55)            $score += 8;
        }

        return min(100, $score);
    }

    private function calcVisaType(string $visaType): int
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

    // -------------------------------------------------------------------------
    // Основная формула скоринга v2
    //
    // Ключевая идея: чем слабее профиль клиента, тем сильнее
    // сложность страны (difficulty_score) влияет на итоговый балл.
    //
    // profile_base = finances*0.30 + visa_history*0.40 + social_ties*0.30
    // sensitivity  = 0.10 + 0.35 * (1 - profile_base/100)
    // country_bonus = (100 - difficulty) * sensitivity
    // score = (profile_base + country_bonus + visa_adj) * red_flag_multiplier
    //
    // Сильный профиль (80): разброс ~8-12 баллов по странам
    // Средний (50): ~13-15 баллов
    // Слабый (30): ~20-30 баллов
    // -------------------------------------------------------------------------

    public function score(PublicUser $user, string $countryCode, string $visaType = 'tourist'): array
    {
        // Шаг 1: Блоки профиля (одинаковы для всех стран)
        $finances    = $this->calcFinances($user);
        $visaHistory = $this->calcVisaHistory($user);
        $socialTies  = $this->calcSocialTies($user);

        // Взвешенная база профиля
        $profileBase = $finances * 0.30 + $visaHistory * 0.40 + $socialTies * 0.30;

        // Шаг 2: Сложность страны
        $country    = $this->countryData($countryCode);
        $difficulty = $country ? ((int) ($country->difficulty_score ?? 50)) : 50;

        // Шаг 3: Тип визы (небольшая корректировка)
        $visaTypeScore = $this->calcVisaType($visaType);
        $visaAdj       = ($visaTypeScore - 60) * 0.08;

        // Шаг 4: Нелинейная комбинация
        // sensitivity растёт при слабом профиле → страна влияет сильнее
        $sensitivity  = 0.10 + 0.35 * (1 - $profileBase / 100);
        $countryBonus = (100 - $difficulty) * $sensitivity;

        $rawScore = $profileBase + $countryBonus + $visaAdj;

        // Шаг 5: Красные флаги
        $multiplier = $this->applyRedFlags($user);
        $total      = (int) round($rawScore * $multiplier);
        $total      = max(5, min(100, $total));

        $thresholds = $this->thresholds();
        $label      = $this->scoreLabel($total, $thresholds);

        // Блоки для отображения (включая destination как инверсию difficulty)
        $blocks = [
            'finances'     => $finances,
            'visa_history' => $visaHistory,
            'social_ties'  => $socialTies,
            'destination'  => max(0, min(100, 100 - $difficulty)),
            'visa_type'    => $visaTypeScore,
        ];

        $breakdown = array_merge($blocks, [
            'profile_base'   => round($profileBase, 1),
            'difficulty'     => $difficulty,
            'country_bonus'  => round($countryBonus, 1),
            'raw_weighted'   => round($rawScore, 1),
        ]);

        $redFlags = $this->getRedFlagDescriptions($user, $multiplier);

        // Кеш
        PublicScoreCache::updateOrCreate(
            ['public_user_id' => $user->id, 'country_code' => $countryCode],
            [
                'score'           => $total,
                'breakdown'       => $breakdown,
                'recommendations' => $this->recommendations($user, $blocks, $countryCode),
                'calculated_at'   => now(),
            ]
        );

        // История
        DB::table('public_score_history')->insert([
            'id'                  => Str::uuid()->toString(),
            'public_user_id'      => $user->id,
            'country_code'        => $countryCode,
            'score'               => $total,
            'breakdown'           => json_encode($breakdown),
            'model_version'       => $this->modelVersion(),
            'red_flag_multiplier' => $multiplier,
            'calculated_at'       => now(),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return [
            'country_code'         => $countryCode,
            'score'                => $total,
            'label'                => $label,
            'breakdown'            => $breakdown,
            'red_flags'            => $redFlags,
            'red_flag_multiplier'  => $multiplier,
            'recommendations'      => $this->recommendations($user, $blocks, $countryCode),
            'profile_percent'      => $user->profileCompleteness(),
        ];
    }

    /**
     * Скоринг по всем доступным странам.
     */
    public function scoreAll(PublicUser $user, string $visaType = 'tourist'): array
    {
        $countries = $this->countryCodes();
        if (empty($countries)) {
            $countries = ['DE','ES','FR','IT','PL','CZ','GB','US','CA','KR','AE'];
        }

        return collect($countries)->map(fn ($cc) => $this->score($user, $cc, $visaType))
            ->sortByDesc('score')
            ->values()
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Вспомогательные методы
    // -------------------------------------------------------------------------

    public function getRedFlagDescriptions(PublicUser $user, float $multiplier): array
    {
        if ($multiplier >= 1.0) return [];

        $flags = [];
        $year  = (int) date('Y');

        if (($user->refusals_count ?? 0) > 2
            && ($user->last_refusal_year ?? 0) >= ($year - 3)) {
            $flags[] = 'Более 2 отказов за последние 3 года — коэффициент снижен до ' . round($multiplier * 100) . '%';
        }
        if ($user->had_overstay) {
            $flags[] = 'Нарушение визового режима — снижает шансы на одобрение';
        }
        if ($user->had_deportation ?? false) {
            $flags[] = 'Депортация — серьёзный негативный фактор';
        }

        return $flags;
    }

    private function recommendations(PublicUser $user, array $blocks, string $cc): array
    {
        $recs = [];

        if ($blocks['finances'] < 50) {
            if (! $user->monthly_income_usd) {
                $recs[] = 'Укажите ежемесячный доход — это значительно повысит скоринг';
            } else {
                $recs[] = 'Подготовьте справку о доходах с места работы';
            }
            $recs[] = 'Добавьте выписку из банка за 3–6 месяцев';
        }

        if ($blocks['visa_history'] < 50) {
            if (! $user->has_schengen_visa && ! $user->has_us_visa) {
                $recs[] = 'Наличие шенгенской или американской визы значительно повышает доверие консульства';
            }
        }

        if ($blocks['social_ties'] < 50) {
            if (! $user->has_property) $recs[] = 'Укажите наличие недвижимости (квартира, земля)';
            if (! $user->has_car)      $recs[] = 'Укажите наличие автомобиля';
            if (! $user->education_level || $user->education_level === 'none') {
                $recs[] = 'Укажите уровень образования — высшее образование повышает доверие консульства';
            }
        }

        if (in_array($cc, ['US', 'CA']) && ! $user->has_us_visa) {
            $recs[] = 'Для США критически важна история поездок и сильная привязанность к родине';
        }

        if ($user->profileCompleteness() < 60) {
            $recs[] = 'Заполните профиль полностью — это увеличит точность прогноза';
        }

        return array_slice($recs, 0, 4);
    }

    /**
     * Рекомендации по профилю (без привязки к стране).
     */
    public function profileRecommendations(PublicUser $user, array $blocks): array
    {
        $recs = [];

        if ($blocks['finances'] < 50) {
            if (!$user->monthly_income_usd) {
                $recs[] = ['type' => 'finances', 'priority' => 'high', 'text' => 'Укажите ежемесячный доход — это значительно повысит скоринг', 'docs' => ['Справка о доходах с места работы', 'Банковская выписка за 3-6 месяцев']];
            } else {
                $recs[] = ['type' => 'finances', 'priority' => 'medium', 'text' => 'Подготовьте документы, подтверждающие финансовую стабильность', 'docs' => ['Справка о доходах с места работы', 'Банковская выписка за 3-6 месяцев', 'Справка о балансе сберегательного счёта']];
            }
            if (!$user->employment_type || $user->employment_type === 'unemployed') {
                $recs[] = ['type' => 'finances', 'priority' => 'high', 'text' => 'Официальное трудоустройство значительно повышает шансы', 'docs' => ['Трудовой договор', 'Приказ о назначении на должность', 'Приказ о предоставлении отпуска']];
            }
        } elseif ($blocks['finances'] < 70) {
            $recs[] = ['type' => 'finances', 'priority' => 'low', 'text' => 'Финансовый профиль достаточный, но можно усилить дополнительными документами', 'docs' => ['Налоговая декларация', 'Справка о депозитах']];
        }

        if ($blocks['visa_history'] < 50) {
            if (!$user->has_schengen_visa && !$user->has_us_visa) {
                $recs[] = ['type' => 'visa_history', 'priority' => 'medium', 'text' => 'Наличие шенгенской или американской визы значительно повышает доверие', 'docs' => ['Копии предыдущих виз', 'Копии штампов в паспорте']];
            }
            if (($user->refusals_count ?? 0) > 0) {
                $recs[] = ['type' => 'visa_history', 'priority' => 'high', 'text' => 'При наличии отказов особенно важно подготовить полный пакет документов', 'docs' => ['Письмо-объяснение причин предыдущего отказа', 'Дополнительные подтверждающие документы']];
            }
        }

        if ($blocks['social_ties'] < 50) {
            if (!$user->has_property) {
                $recs[] = ['type' => 'social_ties', 'priority' => 'medium', 'text' => 'Укажите наличие недвижимости — это главный фактор привязанности к родине', 'docs' => ['Свидетельство о праве собственности на недвижимость']];
            }
            if (!$user->has_car) {
                $recs[] = ['type' => 'social_ties', 'priority' => 'low', 'text' => 'Наличие автомобиля подтверждает имущественную привязанность', 'docs' => ['Техпаспорт автомобиля']];
            }
            if ($user->marital_status !== 'married') {
                $recs[] = ['type' => 'social_ties', 'priority' => 'medium', 'text' => 'Семейные связи — важный фактор привязанности', 'docs' => ['Свидетельство о браке', 'Свидетельства о рождении детей']];
            }
            if (!$user->employment_type || !in_array($user->employment_type, ['employed', 'business_owner'])) {
                $recs[] = ['type' => 'social_ties', 'priority' => 'medium', 'text' => 'Стабильная занятость — ключевой фактор для консульства', 'docs' => ['Приказ о назначении на должность', 'Приказ о предоставлении отпуска', 'Справка с места работы']];
            }
        }

        if ($user->profileCompleteness() < 60) {
            $recs[] = ['type' => 'profile', 'priority' => 'high', 'text' => 'Заполните профиль полностью — это увеличит точность прогноза и улучшит скоринг', 'docs' => []];
        }

        $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($recs, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 9) - ($priorityOrder[$b['priority']] ?? 9));

        return array_slice($recs, 0, 6);
    }

    private function scoreLabel(int $score, array $thresholds): string
    {
        return match (true) {
            $score >= $thresholds['high']   => 'Высокая вероятность',
            $score >= $thresholds['medium'] => 'Средняя вероятность',
            $score >= $thresholds['low']    => 'Низкая вероятность',
            default                         => 'Высокий риск отказа',
        };
    }
}
