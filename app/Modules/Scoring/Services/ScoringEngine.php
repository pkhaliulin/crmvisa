<?php

namespace App\Modules\Scoring\Services;

use App\Modules\Scoring\Models\ClientProfile;
use App\Modules\Scoring\Models\ClientScore;
use App\Modules\Scoring\Models\ScoringCountryWeight;
use App\Modules\Scoring\Services\Blocks\AssetsBlock;
use App\Modules\Scoring\Services\Blocks\EmploymentBlock;
use App\Modules\Scoring\Services\Blocks\FamilyBlock;
use App\Modules\Scoring\Services\Blocks\FinancialBlock;
use App\Modules\Scoring\Services\Blocks\PersonalBlock;
use App\Modules\Scoring\Services\Blocks\TravelHistoryBlock;
use App\Modules\Scoring\Services\Blocks\TravelPurposeBlock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScoringEngine
{
    public function __construct(
        private FinancialBlock    $financial,
        private EmploymentBlock   $employment,
        private FamilyBlock       $family,
        private AssetsBlock       $assets,
        private TravelHistoryBlock $travelHistory,
        private PersonalBlock     $personal,
        private TravelPurposeBlock $travelPurpose,
    ) {}

    /**
     * Рассчитать score для одной страны.
     */
    public function calculate(ClientProfile $profile, string $countryCode): ClientScore
    {
        $weights = $this->loadWeights($countryCode);

        // Вычисляем каждый блок
        $F  = $this->financial->calculate($profile);
        $E  = $this->employment->calculate($profile);
        $FM = $this->family->calculate($profile);
        $A  = $this->assets->calculate($profile);
        $T  = $this->travelHistory->calculate($profile);
        $P  = $this->personal->calculate($profile);
        $G  = $this->travelPurpose->calculate($profile);

        $isBlocked = $P['is_blocked'] ?? false;

        $blockScores = [
            'F'  => $F['score'],
            'E'  => $E['score'],
            'FM' => $FM['score'],
            'A'  => $A['score'],
            'T'  => $T['score'],
            'P'  => $P['score'],
            'G'  => $G['score'],
        ];

        // Взвешенная сумма
        $totalScore = $isBlocked ? 0.0 : $this->applyWeights($blockScores, $weights);

        // Агрегируем флаги и рекомендации
        $allFlags = array_merge(
            $F['flags'], $E['flags'], $FM['flags'],
            $A['flags'], $T['flags'], $P['flags'], $G['flags']
        );
        $allRecs = array_merge(
            $F['recommendations'], $E['recommendations'], $FM['recommendations'],
            $A['recommendations'], $T['recommendations'], $P['recommendations'], $G['recommendations']
        );

        return ClientScore::updateOrCreate(
            ['client_id' => $profile->client_id, 'country_code' => $countryCode],
            [
                'score'           => $totalScore,
                'block_scores'    => $blockScores,
                'flags'           => array_values(array_unique($allFlags)),
                'recommendations' => array_values(array_unique($allRecs)),
                'is_blocked'      => $isBlocked,
                'calculated_at'   => now(),
            ]
        );
    }

    /**
     * Рассчитать score для всех доступных стран.
     */
    public function calculateAll(ClientProfile $profile): Collection
    {
        $countries = DB::table('scoring_country_weights')
            ->distinct()
            ->pluck('country_code');

        return $countries->map(fn ($country) => $this->calculate($profile, $country));
    }

    // -------------------------------------------------------------------------

    private function loadWeights(string $countryCode): array
    {
        $rows = DB::table('scoring_country_weights')
            ->where('country_code', $countryCode)
            ->pluck('weight', 'block_code');

        // Дефолт если страны нет в БД
        if ($rows->isEmpty()) {
            return ['F' => 25, 'E' => 20, 'FM' => 15, 'A' => 15, 'T' => 15, 'P' => 5, 'G' => 5];
        }

        return $rows->toArray();
    }

    private function applyWeights(array $blockScores, array $weights): float
    {
        $total      = 0.0;
        $totalWeight = 0.0;

        foreach ($blockScores as $block => $score) {
            $weight       = $weights[$block] ?? 0;
            $total       += $score * $weight / 100;
            $totalWeight += $weight;
        }

        // Нормируем если сумма весов не 100
        if ($totalWeight > 0 && $totalWeight !== 100.0) {
            $total = $total / $totalWeight * 100;
        }

        return round(min(max($total, 0), 100), 2);
    }
}
