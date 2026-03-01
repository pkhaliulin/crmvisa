<?php

namespace App\Modules\PublicPortal\Services;

use App\Modules\PublicPortal\Models\PublicLead;
use App\Modules\PublicPortal\Models\PublicScoreCache;
use App\Modules\PublicPortal\Models\PublicUser;

class PublicScoringService
{
    // Веса блоков по странам: [finance, ties, travel, profile]
    private const WEIGHTS = [
        'default' => ['finance' => 0.30, 'ties' => 0.40, 'travel' => 0.20, 'profile' => 0.10],
        'US'      => ['finance' => 0.25, 'ties' => 0.50, 'travel' => 0.15, 'profile' => 0.10],
        'CA'      => ['finance' => 0.25, 'ties' => 0.50, 'travel' => 0.15, 'profile' => 0.10],
        'GB'      => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
        'DE'      => ['finance' => 0.40, 'ties' => 0.35, 'travel' => 0.15, 'profile' => 0.10],
        'FR'      => ['finance' => 0.40, 'ties' => 0.35, 'travel' => 0.15, 'profile' => 0.10],
        'ES'      => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
        'IT'      => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
        'PL'      => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
        'CZ'      => ['finance' => 0.35, 'ties' => 0.40, 'travel' => 0.15, 'profile' => 0.10],
        'KR'      => ['finance' => 0.30, 'ties' => 0.45, 'travel' => 0.15, 'profile' => 0.10],
        'AE'      => ['finance' => 0.35, 'ties' => 0.35, 'travel' => 0.20, 'profile' => 0.10],
    ];

    /**
     * Рассчитать и закэшировать скоринг для страны.
     */
    public function score(PublicUser $user, string $countryCode): array
    {
        $weights = self::WEIGHTS[$countryCode] ?? self::WEIGHTS['default'];

        $finance = $this->calcFinance($user);
        $ties    = $this->calcTies($user);
        $travel  = $this->calcTravel($user);
        $profile = $user->profileCompleteness();

        $total = (int) round(
            $finance * $weights['finance'] +
            $ties    * $weights['ties']    +
            $travel  * $weights['travel']  +
            $profile * $weights['profile']
        );

        $total = max(5, min(100, $total));

        $recommendations = $this->recommendations($user, $finance, $ties, $travel, $countryCode);

        $breakdown = [
            'finance' => $finance,
            'ties'    => $ties,
            'travel'  => $travel,
            'profile' => $profile,
        ];

        // Кэшируем результат
        PublicScoreCache::updateOrCreate(
            ['public_user_id' => $user->id, 'country_code' => $countryCode],
            [
                'score'           => $total,
                'breakdown'       => $breakdown,
                'recommendations' => $recommendations,
                'calculated_at'   => now(),
            ]
        );

        // Создаём или обновляем лид
        PublicLead::updateOrCreate(
            ['public_user_id' => $user->id, 'country_code' => $countryCode],
            ['score' => $total, 'visa_type' => 'tourist']
        );

        return [
            'country_code'    => $countryCode,
            'score'           => $total,
            'label'           => $this->scoreLabel($total),
            'breakdown'       => $breakdown,
            'recommendations' => $recommendations,
            'profile_percent' => $user->profileCompleteness(),
        ];
    }

    /**
     * Скоринг по всем доступным странам (для сравнения).
     */
    public function scoreAll(PublicUser $user): array
    {
        $countries = array_keys(self::WEIGHTS);
        unset($countries[array_search('default', $countries)]);

        return collect($countries)->map(fn ($cc) => $this->score($user, $cc))
            ->sortByDesc('score')
            ->values()
            ->toArray();
    }

    // -------------------------------------------------------------------------
    // Блоки расчёта
    // -------------------------------------------------------------------------

    private function calcFinance(PublicUser $user): int
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

        // Доход (уровни)
        $income = $user->monthly_income_usd ?? 0;
        if ($income >= 3000)     $score += 50;
        elseif ($income >= 1500) $score += 35;
        elseif ($income >= 800)  $score += 20;
        elseif ($income >= 400)  $score += 10;

        return min(100, $score);
    }

    private function calcTies(PublicUser $user): int
    {
        $score = 0;

        if ($user->marital_status === 'married') $score += 25;
        if ($user->has_children)                 $score += 25;
        if ($user->has_property)                 $score += 25;
        if ($user->has_car)                      $score += 10;

        // Госслужба = дополнительная привязка
        if ($user->employment_type === 'employed') $score += 15;

        return min(100, $score);
    }

    private function calcTravel(PublicUser $user): int
    {
        $score = 50; // базовый нейтральный

        if ($user->has_schengen_visa) $score += 20;
        if ($user->has_us_visa)       $score += 25;
        if ($user->had_visa_refusal)  $score -= 30;
        if ($user->had_overstay)      $score -= 60;

        return max(0, min(100, $score));
    }

    // -------------------------------------------------------------------------
    // Рекомендации
    // -------------------------------------------------------------------------

    private function recommendations(PublicUser $user, int $finance, int $ties, int $travel, string $cc): array
    {
        $recs = [];

        if ($finance < 50) {
            if (! $user->monthly_income_usd) {
                $recs[] = 'Укажите ваш ежемесячный доход — это значительно повысит скоринг';
            } else {
                $recs[] = 'Подготовьте справку о доходах с места работы';
            }
            $recs[] = 'Добавьте выписку из банка за 3–6 месяцев';
        }

        if ($ties < 50) {
            if (! $user->has_property) $recs[] = 'Добавьте документы на недвижимость (квартира, земля)';
            if (! $user->has_car)      $recs[] = 'Укажите наличие автомобиля';
            if (! $user->has_children) $recs[] = 'Наличие детей в стране повышает шансы на одобрение';
        }

        if ($travel < 50 && ! $user->has_schengen_visa && ! $user->has_us_visa) {
            $recs[] = 'Наличие шенгенской или американской визы значительно повышает доверие консульства';
        }

        if (in_array($cc, ['US', 'CA']) && ! $user->has_us_visa) {
            $recs[] = 'Для США критически важна история поездок и сильная привязанность к родине';
        }

        if ($user->profileCompleteness() < 60) {
            $recs[] = 'Заполните профиль полностью — это увеличит точность прогноза';
        }

        return array_slice($recs, 0, 4); // максимум 4 рекомендации
    }

    private function scoreLabel(int $score): string
    {
        return match (true) {
            $score >= 75 => 'Высокий',
            $score >= 55 => 'Средний',
            $score >= 35 => 'Ниже среднего',
            default      => 'Низкий',
        };
    }
}
