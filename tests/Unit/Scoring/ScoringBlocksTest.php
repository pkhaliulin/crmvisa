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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScoringBlocksTest extends TestCase
{
    // =========================================================================
    // FinancialBlock
    // =========================================================================

    public function test_financial_high_income_official(): void
    {
        $block = new FinancialBlock();
        $profile = $this->makeProfile([
            'monthly_income'      => 6000,
            'income_type'         => 'official',
            'bank_history_months' => 12,
            'bank_balance_stable' => true,
            'has_fixed_deposit'   => true,
            'has_investments'     => true,
        ]);

        $result = $block->calculate($profile);

        $this->assertEquals(100, $result['score']);
        $this->assertEmpty($result['flags']);
        $this->assertEmpty($result['recommendations']);
    }

    public function test_financial_zero_income(): void
    {
        $block = new FinancialBlock();
        $profile = $this->makeProfile(['monthly_income' => 0]);

        $result = $block->calculate($profile);

        $this->assertEquals(0, $result['score']);
        $this->assertNotEmpty($result['flags']);
        $this->assertContains('Доход ниже минимального порога', $result['flags']);
    }

    public function test_financial_mid_income_with_investments(): void
    {
        $block = new FinancialBlock();
        $profile = $this->makeProfile([
            'monthly_income'      => 1500,
            'income_type'         => 'informal',
            'bank_history_months' => 3,
            'has_investments'     => true,
        ]);

        $result = $block->calculate($profile);

        // 20 (income) + 0 (not official) + 0 (bank < 6m) + 0 (no stable) + 0 (no deposit) + 5 (investments) = 25
        // 25 / 85 * 100 = 29.41
        $this->assertEqualsWithDelta(29.41, $result['score'], 0.01);
    }

    // =========================================================================
    // EmploymentBlock
    // =========================================================================

    public function test_employment_government_executive(): void
    {
        $block = new EmploymentBlock();
        $profile = $this->makeProfile([
            'employment_type'      => 'government',
            'position_level'       => 'executive',
            'years_at_current_job' => 5,
            'has_employment_gaps'  => false,
        ]);

        $result = $block->calculate($profile);

        // 50 + 15 + 10 + 10 = 85 / 85 * 100 = 100
        $this->assertEquals(100, $result['score']);
        $this->assertEmpty($result['flags']);
    }

    public function test_employment_unemployed(): void
    {
        $block = new EmploymentBlock();
        $profile = $this->makeProfile([
            'employment_type'     => 'unemployed',
            'has_employment_gaps' => true,
        ]);

        $result = $block->calculate($profile);

        $this->assertEquals(0, $result['score']);
        $this->assertContains('Отсутствие занятости — критический фактор', $result['flags']);
    }

    public function test_employment_student_short_tenure(): void
    {
        $block = new EmploymentBlock();
        $profile = $this->makeProfile([
            'employment_type'      => 'student',
            'years_at_current_job' => 0.5,
            'has_employment_gaps'  => false,
        ]);

        $result = $block->calculate($profile);

        // 10 + 0 (no position level bonus) + 0 (< 1 year) + 10 (no gaps) = 20
        // 20 / 85 * 100 = 23.53
        $this->assertEqualsWithDelta(23.53, $result['score'], 0.01);
    }

    // =========================================================================
    // FamilyBlock
    // =========================================================================

    public function test_family_married_children_home(): void
    {
        $block = new FamilyBlock();
        $profile = $this->makeProfile([
            'marital_status'      => 'married',
            'spouse_employed'     => true,
            'children_count'      => 2,
            'children_staying_home' => true,
            'dependents_count'    => 1,
        ]);

        $result = $block->calculate($profile);

        // 30 (married) + 10 (spouse) + 40 (2 children * 20) + 10 (dependents) = 90
        // 90 / 90 * 100 = 100
        $this->assertEquals(100, $result['score']);
    }

    public function test_family_single_no_children(): void
    {
        $block = new FamilyBlock();
        $profile = $this->makeProfile([
            'marital_status'   => 'single',
            'children_count'   => 0,
            'dependents_count' => 0,
        ]);

        $result = $block->calculate($profile);

        // 10 (single) + 0 + 0 + 0 = 10 / 90 * 100 = 11.11
        $this->assertEqualsWithDelta(11.11, $result['score'], 0.01);
    }

    public function test_family_children_not_staying_flags(): void
    {
        $block = new FamilyBlock();
        $profile = $this->makeProfile([
            'marital_status'        => 'married',
            'children_count'        => 1,
            'children_staying_home' => false,
        ]);

        $result = $block->calculate($profile);

        $this->assertContains('Дети не остаются на родине — риск невозврата', $result['flags']);
    }

    // =========================================================================
    // AssetsBlock
    // =========================================================================

    public function test_assets_full(): void
    {
        $block = new AssetsBlock();
        $profile = $this->makeProfile([
            'has_real_estate' => true,
            'has_car'         => true,
            'has_business'    => true,
        ]);

        $result = $block->calculate($profile);

        // 40 + 20 + 40 = 100 / 100 * 100 = 100
        $this->assertEquals(100, $result['score']);
        $this->assertEmpty($result['flags']);
    }

    public function test_assets_none(): void
    {
        $block = new AssetsBlock();
        $profile = $this->makeProfile([
            'has_real_estate' => false,
            'has_car'         => false,
            'has_business'    => false,
        ]);

        $result = $block->calculate($profile);

        $this->assertEquals(0, $result['score']);
        $this->assertContains('Нет подтверждённых активов в стране проживания', $result['flags']);
    }

    public function test_assets_only_car(): void
    {
        $block = new AssetsBlock();
        $profile = $this->makeProfile([
            'has_real_estate' => false,
            'has_car'         => true,
            'has_business'    => false,
        ]);

        $result = $block->calculate($profile);

        // 20 / 100 * 100 = 20
        $this->assertEquals(20, $result['score']);
    }

    // =========================================================================
    // TravelHistoryBlock
    // =========================================================================

    public function test_travel_strong_history(): void
    {
        $block = new TravelHistoryBlock();
        $profile = $this->makeProfile([
            'has_us_visa'       => true,
            'has_schengen_visa' => true,
            'has_uk_visa'       => true,
            'previous_refusals' => 0,
            'has_overstay'      => false,
        ]);

        $result = $block->calculate($profile);

        // 25 + 20 + 20 + 35 = 100
        $this->assertEquals(100, $result['score']);
    }

    public function test_travel_overstay_and_refusals(): void
    {
        $block = new TravelHistoryBlock();
        $profile = $this->makeProfile([
            'has_us_visa'       => false,
            'has_schengen_visa' => false,
            'has_uk_visa'       => false,
            'previous_refusals' => 2,
            'has_overstay'      => true,
        ]);

        $result = $block->calculate($profile);

        // 0 + 0 + 0 - 40 (refusals) - 40 (overstay) = -80, capped to 0
        $this->assertEquals(0, $result['score']);
        $this->assertNotEmpty($result['flags']);
    }

    public function test_travel_no_history_low_score(): void
    {
        $block = new TravelHistoryBlock();
        $profile = $this->makeProfile([
            'has_us_visa'       => false,
            'has_schengen_visa' => false,
            'has_uk_visa'       => false,
            'previous_refusals' => 0,
            'has_overstay'      => false,
        ]);

        $result = $block->calculate($profile);

        // 0 + 0 + 0 + 35 (no refusals) = 35
        $this->assertEquals(35, $result['score']);
        $this->assertEmpty($result['flags']);
    }

    // =========================================================================
    // PersonalBlock
    // =========================================================================

    public function test_personal_criminal_record_blocks(): void
    {
        $block = new PersonalBlock();
        $profile = $this->makeProfile([
            'has_criminal_record' => true,
            'education_level'     => 'phd',
            'age'                 => 35,
        ]);

        $result = $block->calculate($profile);

        $this->assertEquals(0, $result['score']);
        $this->assertTrue($result['is_blocked']);
    }

    public function test_personal_phd_optimal_age(): void
    {
        $block = new PersonalBlock();
        $profile = $this->makeProfile([
            'has_criminal_record' => false,
            'education_level'     => 'phd',
            'age'                 => 35,
        ]);

        $result = $block->calculate($profile);

        // 30 (phd) + 30 (age 25-45) = 60 / 60 * 100 = 100
        $this->assertEquals(100, $result['score']);
        $this->assertFalse($result['is_blocked']);
    }

    public function test_personal_no_education_young(): void
    {
        $block = new PersonalBlock();
        $profile = $this->makeProfile([
            'has_criminal_record' => false,
            'education_level'     => 'none',
            'age'                 => 20,
        ]);

        $result = $block->calculate($profile);

        // 0 (none) + 20 (age 18-24) = 20 / 60 * 100 = 33.33
        $this->assertEqualsWithDelta(33.33, $result['score'], 0.01);
    }

    // =========================================================================
    // TravelPurposeBlock
    // =========================================================================

    public function test_purpose_full_docs_short_trip(): void
    {
        $block = new TravelPurposeBlock();
        $profile = $this->makeProfile([
            'has_return_ticket'     => true,
            'has_hotel_booking'     => true,
            'has_invitation_letter' => true,
            'travel_purpose'        => 'tourism',
            'trip_duration_days'    => 10,
        ]);

        $result = $block->calculate($profile);

        // 15 + 10 + 10 (tourism invitation) + 5 (short trip) = 40 / 45 * 100 = 88.89
        $this->assertEqualsWithDelta(88.89, $result['score'], 0.01);
    }

    public function test_purpose_business_with_invitation(): void
    {
        $block = new TravelPurposeBlock();
        $profile = $this->makeProfile([
            'has_return_ticket'     => true,
            'has_hotel_booking'     => true,
            'has_invitation_letter' => true,
            'travel_purpose'        => 'business',
            'trip_duration_days'    => 7,
        ]);

        $result = $block->calculate($profile);

        // 15 + 10 + 15 (business invitation) + 5 = 45 / 45 * 100 = 100
        $this->assertEquals(100, $result['score']);
    }

    public function test_purpose_no_docs_long_trip(): void
    {
        $block = new TravelPurposeBlock();
        $profile = $this->makeProfile([
            'has_return_ticket'     => false,
            'has_hotel_booking'     => false,
            'has_invitation_letter' => false,
            'trip_duration_days'    => 90,
        ]);

        $result = $block->calculate($profile);

        // 0 - 5 = -5, capped to 0
        $this->assertEquals(0, $result['score']);
        $this->assertContains('Длительная поездка (>60 дней) повышает риск невозврата', $result['flags']);
    }

    public function test_purpose_recommendations_for_missing_docs(): void
    {
        $block = new TravelPurposeBlock();
        $profile = $this->makeProfile([
            'has_return_ticket'     => false,
            'has_hotel_booking'     => false,
            'has_invitation_letter' => false,
            'trip_duration_days'    => 14,
        ]);

        $result = $block->calculate($profile);

        $this->assertCount(3, $result['recommendations']);
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
            'previous_refusals'    => 0,
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
