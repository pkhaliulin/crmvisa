<?php

namespace App\Modules\PublicPortal\Services;

use App\Modules\PublicPortal\Models\PublicScoreCache;
use App\Modules\PublicPortal\Models\PublicUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicScoringService
{
    // Дефолтные веса если модель не найдена
    private const DEFAULT_WEIGHTS = [
        'finances'     => 0.25,
        'visa_history' => 0.30,
        'social_ties'  => 0.20,
        'destination'  => 0.15,
        'visa_type'    => 0.10,
    ];

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

    private function weights(string $countryCode): array
    {
        $model = $this->activeModel();
        if (! $model) {
            return self::DEFAULT_WEIGHTS;
        }

        $w = json_decode($model->weights, true) ?? self::DEFAULT_WEIGHTS;

        // Получаем страновые веса если переопределены
        $country = $this->countryData($countryCode);
        if ($country) {
            if (($country->weight_finances ?? 0) > 0)     $w['finances']     = (float) $country->weight_finances;
            if (($country->weight_visa_history ?? 0) > 0) $w['visa_history'] = (float) $country->weight_visa_history;
            if (($country->weight_social_ties ?? 0) > 0)  $w['social_ties']  = (float) $country->weight_social_ties;
        }

        return $w;
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
    // Этап 1 — Красные флаги (блокирующие множители)
    // -------------------------------------------------------------------------

    public function applyRedFlags(PublicUser $user): float
    {
        $multiplier = 1.0;
        $rules      = $this->redFlagRules();
        $year       = (int) date('Y');

        foreach ($rules as $rule) {
            switch ($rule['condition']) {
                case 'refusals_3_in_3y':
                    // Больше 2 отказов за 3 года
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
    // Этап 2 — Блоки расчёта (0-100 баллов каждый)
    // -------------------------------------------------------------------------

    private function calcFinances(PublicUser $user): int
    {
        $score = 0;

        // Тип занятости
        $score += match ($user->employment_type) {
            'employed'       => 40,
            'business_owner' => 50,
            'self_employed'  => 35,
            'retired'        => 30,
            'student'        => 10,
            default          => 0,
        };

        // Доход
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

    private function calcVisaHistory(PublicUser $user): int
    {
        $score = 50; // нейтральная база

        $visasObtained = (int) ($user->visas_obtained_count ?? 0);
        $refusals      = (int) ($user->refusals_count ?? 0);

        // Ранее полученные визы
        if ($visasObtained >= 5)     $score += 30;
        elseif ($visasObtained >= 2) $score += 20;
        elseif ($visasObtained >= 1) $score += 10;

        // Наличие сильных виз
        if ($user->has_schengen_visa) $score += 15;
        if ($user->has_us_visa)       $score += 20;

        // Отказы — СНИЖАЮТ скоринг
        if ($refusals >= 3)     $score -= 40;
        elseif ($refusals >= 2) $score -= 30;
        elseif ($refusals >= 1) $score -= 15;

        // Нарушение режима (дополнительно к red flag)
        if ($user->had_overstay) $score -= 20;

        return max(0, min(100, $score));
    }

    private function calcSocialTies(PublicUser $user): int
    {
        $score = 0;

        // Семья
        if ($user->marital_status === 'married') $score += 20;
        if ($user->has_children) {
            $score += 15;
            // Несколько детей — сильнее привязанность
            if (($user->children_count ?? 1) >= 2) $score += 5;
        }

        // Имущество
        if ($user->has_property) $score += 25;
        if ($user->has_car)      $score += 10;

        // Стаж работы
        $empYears = (int) ($user->employed_years ?? 0);
        if ($empYears >= 5)     $score += 20;
        elseif ($empYears >= 2) $score += 10;
        elseif ($empYears >= 1) $score += 5;

        // Занятость как доп. привязка
        if (in_array($user->employment_type, ['employed', 'business_owner'])) $score += 5;

        // Возраст — ключевой фактор для консульств
        if ($user->dob) {
            $age = now()->diffInYears($user->dob);
            if ($age >= 30 && $age <= 55) $score += 15; // оптимальный: семья, стабильность
            elseif ($age >= 25)           $score += 10; // молодой специалист
            elseif ($age >= 18)           $score += 5;  // студент / начало карьеры
            elseif ($age > 55)            $score += 8;  // предпенсионный/пенсионный
        }

        return min(100, $score);
    }

    private function calcDestination(PublicUser $user, string $countryCode): int
    {
        $score = 50; // базовая нейтральная

        $country = $this->countryData($countryCode);
        if ($country) {
            // Бонус/штраф на основе risk_level
            $score += match ($country->risk_level ?? 'medium') {
                'low'  =>  15,
                'high' => -15,
                default => 0,
            };
            // Страновой бонус
            $score += (int) ($country->destination_score_bonus ?? 0);
        }

        return max(0, min(100, $score));
    }

    private function calcVisaType(string $visaType): int
    {
        // Более лёгкие типы получают бонус
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
    // Публичный метод: рассчитать скор для страны
    // -------------------------------------------------------------------------

    public function score(PublicUser $user, string $countryCode, string $visaType = 'tourist'): array
    {
        $weights = $this->weights($countryCode);

        $blocks = [
            'finances'     => $this->calcFinances($user),
            'visa_history' => $this->calcVisaHistory($user),
            'social_ties'  => $this->calcSocialTies($user),
            'destination'  => $this->calcDestination($user, $countryCode),
            'visa_type'    => $this->calcVisaType($visaType),
        ];

        // Этап 2 — взвешенная сумма
        $rawScore = 0;
        foreach ($blocks as $key => $value) {
            $rawScore += $value * ($weights[$key] ?? 0);
        }

        // Этап 1 — красные флаги
        $multiplier = $this->applyRedFlags($user);
        $total = (int) round($rawScore * $multiplier);
        $total = max(5, min(100, $total));

        $thresholds = $this->thresholds();
        $label      = $this->scoreLabel($total, $thresholds);

        $breakdown = array_merge($blocks, [
            'raw_weighted' => round($rawScore, 1),
        ]);

        $redFlags = $this->getRedFlagDescriptions($user, $multiplier);

        // Сохраняем в кеш
        PublicScoreCache::updateOrCreate(
            ['public_user_id' => $user->id, 'country_code' => $countryCode],
            [
                'score'           => $total,
                'breakdown'       => $breakdown,
                'recommendations' => $this->recommendations($user, $blocks, $countryCode),
                'calculated_at'   => now(),
            ]
        );

        // История пересчётов
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

    private function getRedFlagDescriptions(PublicUser $user, float $multiplier): array
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
        }

        if (in_array($cc, ['US', 'CA']) && ! $user->has_us_visa) {
            $recs[] = 'Для США критически важна история поездок и сильная привязанность к родине';
        }

        if ($user->profileCompleteness() < 60) {
            $recs[] = 'Заполните профиль полностью — это увеличит точность прогноза';
        }

        return array_slice($recs, 0, 4);
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
