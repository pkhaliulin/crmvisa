<?php

namespace Tests\Unit\Scoring;

use App\Modules\Scoring\Models\ClientProfile;
use App\Modules\Scoring\Services\Blocks\AssetsBlock;
use App\Modules\Scoring\Services\Blocks\EmploymentBlock;
use App\Modules\Scoring\Services\Blocks\FamilyBlock;
use App\Modules\Scoring\Services\Blocks\FinancialBlock;
use App\Modules\Scoring\Services\Blocks\PersonalBlock;
use App\Modules\Scoring\Services\Blocks\TravelHistoryBlock;
use App\Modules\Scoring\Services\Blocks\TravelPurposeBlock;
use Tests\TestCase;

/**
 * Тест полного расчёта скоринга через все блоки.
 * Проверяет взвешенную сумму с дефолтными весами: F=25, E=20, FM=15, A=15, T=15, P=5, G=5.
 */
class ScoringEngineTest extends TestCase
{
    private FinancialBlock $financial;
    private EmploymentBlock $employment;
    private FamilyBlock $family;
    private AssetsBlock $assets;
    private TravelHistoryBlock $travelHistory;
    private PersonalBlock $personal;
    private TravelPurposeBlock $travelPurpose;

    protected function setUp(): void
    {
        parent::setUp();

        $this->financial     = new FinancialBlock();
        $this->employment    = new EmploymentBlock();
        $this->family        = new FamilyBlock();
        $this->assets        = new AssetsBlock();
        $this->travelHistory = new TravelHistoryBlock();
        $this->personal      = new PersonalBlock();
        $this->travelPurpose = new TravelPurposeBlock();
    }

    public function test_strong_profile_weighted_score_above_90(): void
    {
        $profile = $this->makeProfile([
            'monthly_income'       => 6000,
            'income_type'          => 'official',
            'bank_history_months'  => 12,
            'bank_balance_stable'  => true,
            'has_fixed_deposit'    => true,
            'has_investments'      => true,
            'employment_type'      => 'government',
            'position_level'       => 'executive',
            'years_at_current_job' => 5,
            'has_employment_gaps'  => false,
            'marital_status'       => 'married',
            'spouse_employed'      => true,
            'children_count'       => 2,
            'children_staying_home' => true,
            'dependents_count'     => 1,
            'has_real_estate'      => true,
            'has_car'              => true,
            'has_business'         => true,
            'has_schengen_visa'    => true,
            'has_us_visa'          => true,
            'has_uk_visa'          => true,
            'previous_refusals'    => 0,
            'has_overstay'         => false,
            'education_level'      => 'phd',
            'has_criminal_record'  => false,
            'age'                  => 35,
            'has_return_ticket'    => true,
            'has_hotel_booking'    => true,
            'has_invitation_letter' => true,
            'travel_purpose'       => 'business',
            'trip_duration_days'   => 7,
        ]);

        $total = $this->calculateWeightedScore($profile);

        $this->assertGreaterThanOrEqual(90, $total);
    }

    public function test_criminal_record_gives_zero(): void
    {
        $profile = $this->makeProfile([
            'has_criminal_record' => true,
            'monthly_income'      => 10000,
            'education_level'     => 'phd',
            'age'                 => 35,
        ]);

        $P = $this->personal->calculate($profile);

        $this->assertTrue($P['is_blocked'] ?? false);
        $this->assertEquals(0, $P['score']);

        // Когда blocked, общий score = 0
        $total = $this->calculateWeightedScore($profile);
        $this->assertEquals(0, $total);
    }

    public function test_empty_profile_gives_low_score(): void
    {
        $profile = $this->makeProfile();

        $total = $this->calculateWeightedScore($profile);

        $this->assertLessThanOrEqual(30, $total);
    }

    public function test_all_block_scores_between_0_and_100(): void
    {
        $profiles = [
            'empty' => $this->makeProfile(),
            'mid'   => $this->makeProfile([
                'monthly_income'   => 1500,
                'employment_type'  => 'private',
                'marital_status'   => 'married',
                'has_car'          => true,
                'has_schengen_visa' => true,
                'education_level'  => 'bachelor',
                'age'              => 30,
                'has_return_ticket' => true,
            ]),
            'worst' => $this->makeProfile([
                'previous_refusals' => 5,
                'has_overstay'      => true,
            ]),
        ];

        foreach ($profiles as $name => $profile) {
            $blocks = $this->getAllBlockScores($profile);
            foreach ($blocks as $block => $score) {
                $this->assertGreaterThanOrEqual(0, $score, "$name: $block score should be >= 0");
                $this->assertLessThanOrEqual(100, $score, "$name: $block score should be <= 100");
            }
        }
    }

    public function test_weighted_score_between_0_and_100(): void
    {
        // Наихудший сценарий
        $worst = $this->makeProfile([
            'previous_refusals'   => 5,
            'has_overstay'        => true,
            'employment_type'     => 'unemployed',
            'has_employment_gaps' => true,
        ]);
        $worstScore = $this->calculateWeightedScore($worst);
        $this->assertGreaterThanOrEqual(0, $worstScore);
        $this->assertLessThanOrEqual(100, $worstScore);

        // Наилучший сценарий
        $best = $this->makeProfile([
            'monthly_income'       => 10000,
            'income_type'          => 'official',
            'bank_history_months'  => 24,
            'bank_balance_stable'  => true,
            'has_fixed_deposit'    => true,
            'has_investments'      => true,
            'employment_type'      => 'government',
            'position_level'       => 'executive',
            'years_at_current_job' => 10,
            'has_employment_gaps'  => false,
            'marital_status'       => 'married',
            'spouse_employed'      => true,
            'children_count'       => 3,
            'children_staying_home' => true,
            'dependents_count'     => 2,
            'has_real_estate'      => true,
            'has_car'              => true,
            'has_business'         => true,
            'has_schengen_visa'    => true,
            'has_us_visa'          => true,
            'has_uk_visa'          => true,
            'previous_refusals'    => 0,
            'education_level'      => 'phd',
            'age'                  => 35,
            'has_return_ticket'    => true,
            'has_hotel_booking'    => true,
            'has_invitation_letter' => true,
            'travel_purpose'       => 'business',
            'trip_duration_days'   => 7,
        ]);
        $bestScore = $this->calculateWeightedScore($best);
        $this->assertGreaterThanOrEqual(0, $bestScore);
        $this->assertLessThanOrEqual(100, $bestScore);
    }

    public function test_different_profiles_produce_different_scores(): void
    {
        $weak = $this->makeProfile([
            'monthly_income'   => 300,
            'employment_type'  => 'unemployed',
            'marital_status'   => 'single',
        ]);

        $strong = $this->makeProfile([
            'monthly_income'       => 5000,
            'income_type'          => 'official',
            'employment_type'      => 'government',
            'position_level'       => 'senior',
            'marital_status'       => 'married',
            'has_real_estate'      => true,
            'has_schengen_visa'    => true,
            'education_level'      => 'master',
            'age'                  => 35,
        ]);

        $weakScore = $this->calculateWeightedScore($weak);
        $strongScore = $this->calculateWeightedScore($strong);

        $this->assertGreaterThan($weakScore, $strongScore);
    }

    public function test_flags_aggregated_from_all_blocks(): void
    {
        $profile = $this->makeProfile([
            'monthly_income'      => 0,
            'employment_type'     => 'unemployed',
            'has_employment_gaps' => true,
            'previous_refusals'   => 3,
            'has_overstay'        => true,
        ]);

        $allFlags = $this->getAllFlags($profile);

        $this->assertGreaterThanOrEqual(3, count($allFlags));
    }

    // =========================================================================
    // Вычисление взвешенного score (копия логики ScoringEngine)
    // =========================================================================

    private function calculateWeightedScore(ClientProfile $profile): float
    {
        $weights = ['F' => 25, 'E' => 20, 'FM' => 15, 'A' => 15, 'T' => 15, 'P' => 5, 'G' => 5];

        $F  = $this->financial->calculate($profile);
        $E  = $this->employment->calculate($profile);
        $FM = $this->family->calculate($profile);
        $A  = $this->assets->calculate($profile);
        $T  = $this->travelHistory->calculate($profile);
        $P  = $this->personal->calculate($profile);
        $G  = $this->travelPurpose->calculate($profile);

        $isBlocked = $P['is_blocked'] ?? false;
        if ($isBlocked) {
            return 0.0;
        }

        $blockScores = [
            'F'  => $F['score'],
            'E'  => $E['score'],
            'FM' => $FM['score'],
            'A'  => $A['score'],
            'T'  => $T['score'],
            'P'  => $P['score'],
            'G'  => $G['score'],
        ];

        $total = 0.0;
        foreach ($blockScores as $block => $score) {
            $total += $score * ($weights[$block] ?? 0) / 100;
        }

        return round(min(max($total, 0), 100), 2);
    }

    private function getAllBlockScores(ClientProfile $profile): array
    {
        return [
            'F'  => $this->financial->calculate($profile)['score'],
            'E'  => $this->employment->calculate($profile)['score'],
            'FM' => $this->family->calculate($profile)['score'],
            'A'  => $this->assets->calculate($profile)['score'],
            'T'  => $this->travelHistory->calculate($profile)['score'],
            'P'  => $this->personal->calculate($profile)['score'],
            'G'  => $this->travelPurpose->calculate($profile)['score'],
        ];
    }

    private function getAllFlags(ClientProfile $profile): array
    {
        $flags = [];
        $blocks = [
            $this->financial->calculate($profile),
            $this->employment->calculate($profile),
            $this->family->calculate($profile),
            $this->assets->calculate($profile),
            $this->travelHistory->calculate($profile),
            $this->personal->calculate($profile),
            $this->travelPurpose->calculate($profile),
        ];
        foreach ($blocks as $result) {
            $flags = array_merge($flags, $result['flags']);
        }
        return array_unique($flags);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeProfile(array $attrs = []): ClientProfile
    {
        $defaults = [
            'client_id'             => 'test-client-id',
            'monthly_income'        => 0,
            'income_type'           => null,
            'bank_balance'          => 0,
            'bank_history_months'   => 0,
            'bank_balance_stable'   => false,
            'has_fixed_deposit'     => false,
            'fixed_deposit_amount'  => 0,
            'has_investments'       => false,
            'investments_amount'    => 0,
            'employment_type'       => null,
            'employer_name'         => null,
            'position'              => null,
            'position_level'        => null,
            'years_at_current_job'  => 0,
            'total_work_experience' => 0,
            'has_employment_gaps'   => false,
            'marital_status'        => null,
            'spouse_employed'       => false,
            'children_count'        => 0,
            'children_staying_home' => false,
            'dependents_count'      => 0,
            'has_real_estate'       => false,
            'has_car'               => false,
            'has_business'          => false,
            'has_schengen_visa'     => false,
            'has_us_visa'           => false,
            'has_uk_visa'           => false,
            'previous_refusals'     => 0,
            'has_overstay'          => false,
            'education_level'       => null,
            'has_criminal_record'   => false,
            'age'                   => null,
            'travel_purpose'        => null,
            'has_return_ticket'     => false,
            'has_hotel_booking'     => false,
            'has_invitation_letter' => false,
            'trip_duration_days'    => 0,
            'sponsor_covers_expenses' => false,
        ];

        $profile = new ClientProfile();
        $profile->forceFill(array_merge($defaults, $attrs));

        return $profile;
    }
}
