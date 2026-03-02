<?php

namespace App\Modules\Scoring\Services;

use App\Modules\Scoring\Models\ClientProfile;
use Illuminate\Support\Facades\DB;

class RecommendationEngine
{
    /**
     * Генерация приоритизированных рекомендаций для конкретной страны.
     *
     * @return array{recommendations: array, weak_blocks: array, profile_completeness: int}
     */
    public function generate(ClientProfile $profile, string $countryCode, array $blockScores): array
    {
        $weights = $this->loadWeights($countryCode);

        $improvements = array_merge(
            $this->financialImprovements($profile, $weights['F'] ?? 25),
            $this->employmentImprovements($profile, $weights['E'] ?? 20),
            $this->familyImprovements($profile, $weights['FM'] ?? 15),
            $this->assetsImprovements($profile, $weights['A'] ?? 15),
            $this->travelHistoryImprovements($profile, $weights['T'] ?? 15),
            $this->personalImprovements($profile, $weights['P'] ?? 5),
            $this->travelPurposeImprovements($profile, $weights['G'] ?? 5),
        );

        // Сортируем по impact (убывание)
        usort($improvements, fn ($a, $b) => $b['impact'] <=> $a['impact']);

        // Слабые блоки — где score < 50
        $weakBlocks = [];
        $blockLabels = [
            'F'  => 'Финансы',
            'E'  => 'Занятость',
            'FM' => 'Семейные связи',
            'A'  => 'Имущество',
            'T'  => 'История поездок',
            'P'  => 'Личные данные',
            'G'  => 'Цель поездки',
        ];

        foreach ($blockScores as $code => $score) {
            if ($score < 50) {
                $weakBlocks[] = [
                    'block'  => $code,
                    'label'  => $blockLabels[$code] ?? $code,
                    'score'  => round($score, 1),
                    'weight' => $weights[$code] ?? 0,
                ];
            }
        }

        usort($weakBlocks, fn ($a, $b) => $b['weight'] <=> $a['weight']);

        return [
            'recommendations'      => array_slice($improvements, 0, 10),
            'weak_blocks'          => $weakBlocks,
            'profile_completeness' => $this->profileCompleteness($profile),
        ];
    }

    /**
     * Генерация рекомендаций для всех стран сразу — топ-N общих советов.
     */
    public function generateGlobal(ClientProfile $profile, array $allScores): array
    {
        // Собираем рекомендации по всем странам, суммируя impact
        $impactMap = [];

        foreach ($allScores as $score) {
            $countryRecs = $this->generate($profile, $score->country_code, $score->block_scores ?? []);
            foreach ($countryRecs['recommendations'] as $rec) {
                $key = $rec['field'];
                if (! isset($impactMap[$key])) {
                    $impactMap[$key] = $rec;
                    $impactMap[$key]['impact'] = 0;
                    $impactMap[$key]['affects_countries'] = [];
                }
                $impactMap[$key]['impact'] += $rec['impact'];
                $impactMap[$key]['affects_countries'][] = $score->country_code;
            }
        }

        $global = array_values($impactMap);
        usort($global, fn ($a, $b) => $b['impact'] <=> $a['impact']);

        return array_slice($global, 0, 8);
    }

    // =========================================================================
    // Блок F — Финансы (MAX_RAW = 85)
    // =========================================================================

    private function financialImprovements(ClientProfile $p, float $blockWeight): array
    {
        $maxRaw = 85;
        $recs   = [];

        // Доход
        if (($p->monthly_income ?? 0) < 500) {
            $recs[] = $this->makeRec(
                field:    'monthly_income',
                block:    'F',
                title:    'Подтвердите доход от $500/мес',
                detail:   'Официальная справка о доходе — один из ключевых документов для консульства.',
                document: 'Справка 2-НДФЛ или справка с места работы',
                rawDelta: 10,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        } elseif ($p->monthly_income < 2000) {
            $currentRaw = match (true) {
                $p->monthly_income >= 1000 => 20,
                $p->monthly_income >= 500  => 10,
                default                    => 0,
            };
            $recs[] = $this->makeRec(
                field:    'monthly_income',
                block:    'F',
                title:    'Увеличьте подтверждённый доход до $2000+/мес',
                detail:   'Доход свыше $2000 значительно повышает финансовый блок.',
                document: 'Справка о зарплате, выписки по депозитам, доход от аренды',
                rawDelta: 35 - $currentRaw,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        // Тип дохода
        if ($p->income_type !== 'official') {
            $recs[] = $this->makeRec(
                field:    'income_type',
                block:    'F',
                title:    'Подтвердите официальный источник дохода',
                detail:   'Официальный доход (трудовой договор) даёт бонус +10 баллов.',
                document: 'Трудовой договор, выписка из пенсионного фонда',
                rawDelta: 10,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        // Банковская выписка
        if (($p->bank_history_months ?? 0) < 6) {
            $recs[] = $this->makeRec(
                field:    'bank_history_months',
                block:    'F',
                title:    'Предоставьте выписку банка за 6+ месяцев',
                detail:   'Стабильная банковская история — обязательное требование большинства консульств.',
                document: 'Выписка из банка за последние 6 месяцев',
                rawDelta: 10,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        // Депозит
        if (! $p->has_fixed_deposit) {
            $recs[] = $this->makeRec(
                field:    'has_fixed_deposit',
                block:    'F',
                title:    'Откройте срочный депозит в банке',
                detail:   'Наличие депозита показывает финансовую стабильность и привязанность к стране.',
                document: 'Справка о наличии депозита',
                rawDelta: 5,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        // Инвестиции
        if (! $p->has_investments) {
            $recs[] = $this->makeRec(
                field:    'has_investments',
                block:    'F',
                title:    'Укажите инвестиции (акции, облигации, ПИФы)',
                detail:   'Инвестиционный портфель дополнительно подтверждает финансовую состоятельность.',
                document: 'Выписка брокерского счёта',
                rawDelta: 5,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        return $recs;
    }

    // =========================================================================
    // Блок E — Занятость (MAX_RAW = 85)
    // =========================================================================

    private function employmentImprovements(ClientProfile $p, float $blockWeight): array
    {
        $maxRaw = 85;
        $recs   = [];

        if ($p->employment_type === 'unemployed' || ! $p->employment_type) {
            $recs[] = $this->makeRec(
                field:    'employment_type',
                block:    'E',
                title:    'Укажите текущую занятость',
                detail:   'Безработные заявители получают 0 баллов. Любой тип занятости значительно повысит оценку.',
                document: 'Трудовой договор, свидетельство ИП, справка о зачислении в ВУЗ',
                rawDelta: 30,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (($p->years_at_current_job ?? 0) < 1 && $p->employment_type && $p->employment_type !== 'unemployed') {
            $recs[] = $this->makeRec(
                field:    'years_at_current_job',
                block:    'E',
                title:    'Стаж на текущем месте менее 1 года',
                detail:   'Стаж от 1 года даёт +5, от 3 лет — +10 баллов. Длительная работа — сильный фактор.',
                document: 'Справка с места работы с указанием даты начала',
                rawDelta: 5,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if ($p->has_employment_gaps) {
            $recs[] = $this->makeRec(
                field:    'has_employment_gaps',
                block:    'E',
                title:    'Подготовьте объяснение пробелов в занятости',
                detail:   'Отсутствие пробелов даёт +10 баллов. Подготовьте документы, объясняющие перерывы.',
                document: 'Письмо-объяснение, справки об обучении/лечении',
                rawDelta: 10,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (! $p->position_level || ! in_array($p->position_level, ['executive', 'senior'])) {
            // Рекомендация только если есть работа
            if ($p->employment_type && ! in_array($p->employment_type, ['unemployed', 'student', 'retired'])) {
                $recs[] = $this->makeRec(
                    field:    'position_level',
                    block:    'E',
                    title:    'Укажите уровень должности (senior/executive)',
                    detail:   'Руководящая должность даёт до +15 баллов.',
                    document: 'Справка с указанием должности',
                    rawDelta: 10,
                    maxRaw:   $maxRaw,
                    weight:   $blockWeight,
                );
            }
        }

        return $recs;
    }

    // =========================================================================
    // Блок FM — Семья (MAX_RAW = 90)
    // =========================================================================

    private function familyImprovements(ClientProfile $p, float $blockWeight): array
    {
        $maxRaw = 90;
        $recs   = [];

        if ($p->marital_status === 'single' || ! $p->marital_status) {
            $recs[] = $this->makeRec(
                field:    'marital_status',
                block:    'FM',
                title:    'Семейное положение: холост/не указано',
                detail:   'Состоящие в браке получают +30 баллов. Это один из сильнейших факторов привязанности к родине.',
                document: 'Свидетельство о браке',
                rawDelta: 20,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if ($p->marital_status === 'married' && ! $p->spouse_employed) {
            $recs[] = $this->makeRec(
                field:    'spouse_employed',
                block:    'FM',
                title:    'Подтвердите занятость супруга(и)',
                detail:   'Работающий супруг(а) — дополнительная привязанность к стране (+10 баллов).',
                document: 'Справка с места работы супруга(и)',
                rawDelta: 10,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (($p->children_count ?? 0) > 0 && ! $p->children_staying_home) {
            $recs[] = $this->makeRec(
                field:    'children_staying_home',
                block:    'FM',
                title:    'Укажите, что дети остаются дома',
                detail:   'Дети, остающиеся на родине — сильнейший фактор привязанности (до +40 баллов).',
                document: 'Свидетельства о рождении детей',
                rawDelta: min($p->children_count * 20, 40),
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (($p->dependents_count ?? 0) === 0) {
            $recs[] = $this->makeRec(
                field:    'dependents_count',
                block:    'FM',
                title:    'Укажите иждивенцев (пожилые родители)',
                detail:   'Наличие иждивенцев на родине усиливает привязанность (+10 баллов).',
                document: 'Документы, подтверждающие иждивенцев',
                rawDelta: 10,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        return $recs;
    }

    // =========================================================================
    // Блок A — Активы (MAX_RAW = 100)
    // =========================================================================

    private function assetsImprovements(ClientProfile $p, float $blockWeight): array
    {
        $maxRaw = 100;
        $recs   = [];

        if (! $p->has_real_estate) {
            $recs[] = $this->makeRec(
                field:    'has_real_estate',
                block:    'A',
                title:    'Укажите наличие недвижимости',
                detail:   'Недвижимость — самый весомый фактор в блоке имущества (+40 баллов). Квартира, дом, земельный участок.',
                document: 'Выписка из кадастра, свидетельство о собственности',
                rawDelta: 40,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (! $p->has_car) {
            $recs[] = $this->makeRec(
                field:    'has_car',
                block:    'A',
                title:    'Укажите наличие автомобиля',
                detail:   'Автомобиль на имени — дополнительное подтверждение привязанности к стране (+20 баллов).',
                document: 'Техпаспорт (свидетельство о регистрации ТС)',
                rawDelta: 20,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (! $p->has_business) {
            $recs[] = $this->makeRec(
                field:    'has_business',
                block:    'A',
                title:    'Укажите наличие бизнеса',
                detail:   'Собственный бизнес — сильная привязанность к стране (+40 баллов).',
                document: 'Свидетельство о регистрации ИП/ООО, выписка из реестра',
                rawDelta: 40,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        return $recs;
    }

    // =========================================================================
    // Блок T — История поездок (без MAX_RAW, score = raw capped 0-100)
    // =========================================================================

    private function travelHistoryImprovements(ClientProfile $p, float $blockWeight): array
    {
        $recs = [];

        // Эти рекомендации условны — нельзя "добавить" визу, но можно подсказать стратегию
        if (! $p->has_schengen_visa && ! $p->has_us_visa && ! $p->has_uk_visa) {
            $recs[] = $this->makeRec(
                field:    'travel_history_strategy',
                block:    'T',
                title:    'Начните с более лояльных направлений',
                detail:   'Нет истории виз. Рекомендуем сначала получить визу ОАЭ или Турции, затем подаваться на Шенген.',
                document: null,
                rawDelta: 20,
                maxRaw:   100,
                weight:   $blockWeight,
            );
        }

        if (($p->previous_refusals ?? 0) > 0) {
            $recs[] = $this->makeRec(
                field:    'previous_refusals',
                block:    'T',
                title:    'Подготовьте объяснение предыдущих отказов',
                detail:   'Каждый отказ снижает оценку на 20 баллов. Подготовьте cover letter с объяснением.',
                document: 'Cover letter, дополнительные финансовые документы',
                rawDelta: min($p->previous_refusals * 20, 40),
                maxRaw:   100,
                weight:   $blockWeight,
            );
        }

        if ($p->has_overstay) {
            $recs[] = $this->makeRec(
                field:    'has_overstay',
                block:    'T',
                title:    'Нарушение сроков пребывания — критический фактор',
                detail:   'Overstay снижает оценку на 40 баллов. Обязательна юридическая консультация перед подачей.',
                document: 'Справка от иммиграционного юриста, объяснительное письмо',
                rawDelta: 40,
                maxRaw:   100,
                weight:   $blockWeight,
            );
        }

        return $recs;
    }

    // =========================================================================
    // Блок P — Личные данные (MAX_RAW = 60)
    // =========================================================================

    private function personalImprovements(ClientProfile $p, float $blockWeight): array
    {
        $maxRaw = 60;
        $recs   = [];

        if (! $p->education_level || $p->education_level === 'none' || $p->education_level === 'secondary') {
            $currentRaw = match ($p->education_level) {
                'secondary' => 10,
                default     => 0,
            };
            $recs[] = $this->makeRec(
                field:    'education_level',
                block:    'P',
                title:    'Укажите уровень образования',
                detail:   'Высшее образование (bachelor +20, master +25, PhD +30 баллов) повышает доверие консульства.',
                document: 'Диплом об образовании (с переводом)',
                rawDelta: 20 - $currentRaw,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        return $recs;
    }

    // =========================================================================
    // Блок G — Цель поездки (MAX_RAW = 45)
    // =========================================================================

    private function travelPurposeImprovements(ClientProfile $p, float $blockWeight): array
    {
        $maxRaw = 45;
        $recs   = [];

        if (! $p->has_return_ticket) {
            $recs[] = $this->makeRec(
                field:    'has_return_ticket',
                block:    'G',
                title:    'Купите обратный билет',
                detail:   'Обратный билет — один из главных подтверждений намерения вернуться (+15 баллов).',
                document: 'Бронирование или билет на обратный рейс',
                rawDelta: 15,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (! $p->has_hotel_booking) {
            $recs[] = $this->makeRec(
                field:    'has_hotel_booking',
                block:    'G',
                title:    'Забронируйте отель',
                detail:   'Бронирование жилья показывает ясный план поездки (+10 баллов).',
                document: 'Подтверждение бронирования (Booking, Hotels)',
                rawDelta: 10,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (! $p->has_invitation_letter) {
            $rawDelta = $p->travel_purpose === 'business' ? 15 : 10;
            $recs[] = $this->makeRec(
                field:    'has_invitation_letter',
                block:    'G',
                title:    'Получите письмо-приглашение',
                detail:   'Приглашение от принимающей стороны значительно усиливает заявку (+' . $rawDelta . ' баллов).',
                document: 'Invitation letter от компании/друзей/родственников',
                rawDelta: $rawDelta,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        if (($p->trip_duration_days ?? 0) > 60) {
            $recs[] = $this->makeRec(
                field:    'trip_duration_days',
                block:    'G',
                title:    'Сократите планируемый срок поездки',
                detail:   'Поездки дольше 60 дней снижают оценку. Короткие поездки (до 14 дней) дают бонус.',
                document: null,
                rawDelta: 10,
                maxRaw:   $maxRaw,
                weight:   $blockWeight,
            );
        }

        return $recs;
    }

    // =========================================================================
    // Утилиты
    // =========================================================================

    private function makeRec(
        string  $field,
        string  $block,
        string  $title,
        string  $detail,
        ?string $document,
        int     $rawDelta,
        int     $maxRaw,
        float   $weight,
    ): array {
        // Прирост в блоке: rawDelta / maxRaw * 100
        // Прирост в общем score: blockDelta * weight / 100
        $blockDelta = ($rawDelta / $maxRaw) * 100;
        $totalImpact = round($blockDelta * $weight / 100, 1);

        $rec = [
            'field'        => $field,
            'block'        => $block,
            'title'        => $title,
            'detail'       => $detail,
            'impact'       => $totalImpact,
            'impact_label' => '+' . $totalImpact . '% к общему score',
        ];

        if ($document) {
            $rec['document'] = $document;
        }

        return $rec;
    }

    private function loadWeights(string $countryCode): array
    {
        $rows = DB::table('scoring_country_weights')
            ->where('country_code', $countryCode)
            ->pluck('weight', 'block_code');

        if ($rows->isEmpty()) {
            return ['F' => 25, 'E' => 20, 'FM' => 15, 'A' => 15, 'T' => 15, 'P' => 5, 'G' => 5];
        }

        return $rows->toArray();
    }

    private function profileCompleteness(ClientProfile $p): int
    {
        $fields = [
            'monthly_income', 'income_type', 'bank_history_months', 'bank_balance_stable',
            'employment_type', 'years_at_current_job',
            'marital_status', 'children_count',
            'has_real_estate', 'has_car', 'has_business',
            'has_schengen_visa', 'has_us_visa', 'has_uk_visa', 'previous_refusals',
            'education_level', 'age',
            'travel_purpose', 'has_return_ticket', 'has_hotel_booking', 'trip_duration_days',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if ($p->{$field} !== null && $p->{$field} !== '' && $p->{$field} !== 0) {
                $filled++;
            }
        }

        return (int) round($filled / count($fields) * 100);
    }
}
