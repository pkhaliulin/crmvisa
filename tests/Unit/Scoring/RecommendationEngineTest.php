<?php

namespace Tests\Unit\Scoring;

use App\Modules\Scoring\Models\ClientProfile;
use App\Modules\Scoring\Services\RecommendationEngine;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecommendationEngineTest extends TestCase
{
    private RecommendationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RecommendationEngine();

        // Мокаем scoring_country_weights — возвращаем пустую коллекцию (используются дефолты)
        DB::shouldReceive('table')
            ->with('scoring_country_weights')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('pluck')
            ->andReturn(collect([]));
    }

    public function test_weak_profile_generates_many_recommendations(): void
    {
        $profile = $this->makeProfile([
            'monthly_income'  => 0,
            'employment_type' => 'unemployed',
            'marital_status'  => 'single',
            'has_real_estate'  => false,
            'has_car'          => false,
            'has_business'     => false,
            'has_return_ticket'  => false,
            'has_hotel_booking'  => false,
        ]);

        $blockScores = [
            'F' => 0, 'E' => 0, 'FM' => 11, 'A' => 0, 'T' => 35, 'P' => 50, 'G' => 0,
        ];

        $result = $this->engine->generate($profile, 'DE', $blockScores);

        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('weak_blocks', $result);
        $this->assertArrayHasKey('profile_completeness', $result);

        // Должно быть много рекомендаций для слабого профиля
        $this->assertGreaterThanOrEqual(5, count($result['recommendations']));

        // Слабые блоки — где score < 50
        $weakCodes = array_column($result['weak_blocks'], 'block');
        $this->assertContains('F', $weakCodes);
        $this->assertContains('E', $weakCodes);
        $this->assertContains('A', $weakCodes);
        $this->assertContains('G', $weakCodes);
        $this->assertContains('FM', $weakCodes);
    }

    public function test_strong_profile_generates_few_recommendations(): void
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
            'education_level'      => 'master',
            'age'                  => 35,
            'has_return_ticket'    => true,
            'has_hotel_booking'    => true,
            'has_invitation_letter' => true,
            'trip_duration_days'   => 10,
        ]);

        $blockScores = [
            'F' => 100, 'E' => 100, 'FM' => 100, 'A' => 100, 'T' => 100, 'P' => 100, 'G' => 100,
        ];

        $result = $this->engine->generate($profile, 'DE', $blockScores);

        // Сильный профиль — мало рекомендаций
        $this->assertLessThanOrEqual(2, count($result['recommendations']));

        // Нет слабых блоков
        $this->assertEmpty($result['weak_blocks']);
    }

    public function test_recommendations_sorted_by_impact(): void
    {
        $profile = $this->makeProfile([
            'monthly_income'    => 0,
            'has_real_estate'   => false,
            'has_return_ticket' => false,
        ]);

        $blockScores = [
            'F' => 0, 'E' => 30, 'FM' => 30, 'A' => 0, 'T' => 50, 'P' => 50, 'G' => 0,
        ];

        $result = $this->engine->generate($profile, 'DE', $blockScores);

        $impacts = array_column($result['recommendations'], 'impact');
        $sorted = $impacts;
        rsort($sorted);

        $this->assertEquals($sorted, $impacts, 'Рекомендации должны быть отсортированы по impact убывания');
    }

    public function test_recommendation_has_required_fields(): void
    {
        $profile = $this->makeProfile(['monthly_income' => 0]);

        $blockScores = [
            'F' => 0, 'E' => 0, 'FM' => 0, 'A' => 0, 'T' => 0, 'P' => 0, 'G' => 0,
        ];

        $result = $this->engine->generate($profile, 'US', $blockScores);

        $rec = $result['recommendations'][0];
        $this->assertArrayHasKey('field', $rec);
        $this->assertArrayHasKey('block', $rec);
        $this->assertArrayHasKey('title', $rec);
        $this->assertArrayHasKey('detail', $rec);
        $this->assertArrayHasKey('impact', $rec);
        $this->assertArrayHasKey('impact_label', $rec);
    }

    public function test_impact_is_positive_number(): void
    {
        $profile = $this->makeProfile([
            'monthly_income'  => 0,
            'has_real_estate' => false,
        ]);

        $blockScores = [
            'F' => 0, 'E' => 0, 'FM' => 0, 'A' => 0, 'T' => 0, 'P' => 0, 'G' => 0,
        ];

        $result = $this->engine->generate($profile, 'DE', $blockScores);

        foreach ($result['recommendations'] as $rec) {
            $this->assertGreaterThan(0, $rec['impact'], "Impact для '{$rec['title']}' должен быть > 0");
        }
    }

    public function test_max_10_recommendations(): void
    {
        $profile = $this->makeProfile(); // Пустой профиль — максимум рекомендаций

        $blockScores = [
            'F' => 0, 'E' => 0, 'FM' => 0, 'A' => 0, 'T' => 0, 'P' => 0, 'G' => 0,
        ];

        $result = $this->engine->generate($profile, 'DE', $blockScores);

        $this->assertLessThanOrEqual(10, count($result['recommendations']));
    }

    public function test_profile_completeness_calculation(): void
    {
        // Профиль с null-значениями — низкий %
        $profile = new ClientProfile();
        $profile->forceFill(['client_id' => 'test']);
        // Все числовые поля = null (не заполнены)

        $result = $this->engine->generate($profile, 'DE', [
            'F' => 0, 'E' => 0, 'FM' => 0, 'A' => 0, 'T' => 0, 'P' => 0, 'G' => 0,
        ]);
        $this->assertLessThanOrEqual(20, $result['profile_completeness']);

        // Полный профиль — высокий %
        $full = $this->makeProfile([
            'monthly_income'       => 3000,
            'income_type'          => 'official',
            'bank_history_months'  => 6,
            'bank_balance_stable'  => true,
            'employment_type'      => 'private',
            'years_at_current_job' => 3,
            'marital_status'       => 'married',
            'children_count'       => 2,
            'has_real_estate'      => true,
            'has_car'              => true,
            'has_business'         => true,
            'has_schengen_visa'    => true,
            'has_us_visa'          => true,
            'has_uk_visa'          => true,
            'previous_refusals'    => 1,
            'education_level'      => 'master',
            'age'                  => 35,
            'travel_purpose'       => 'tourism',
            'has_return_ticket'    => true,
            'has_hotel_booking'    => true,
            'trip_duration_days'   => 10,
        ]);
        $resultFull = $this->engine->generate($full, 'DE', [
            'F' => 80, 'E' => 70, 'FM' => 80, 'A' => 100, 'T' => 60, 'P' => 90, 'G' => 80,
        ]);
        $this->assertGreaterThanOrEqual(90, $resultFull['profile_completeness']);
    }

    public function test_weak_blocks_sorted_by_weight(): void
    {
        $profile = $this->makeProfile();

        $blockScores = [
            'F' => 10, 'E' => 10, 'FM' => 10, 'A' => 10, 'T' => 10, 'P' => 10, 'G' => 10,
        ];

        $result = $this->engine->generate($profile, 'DE', $blockScores);

        $weights = array_column($result['weak_blocks'], 'weight');
        $sorted = $weights;
        rsort($sorted);

        $this->assertEquals($sorted, $weights, 'Слабые блоки должны быть отсортированы по weight убывания');
    }

    public function test_overstay_generates_critical_recommendation(): void
    {
        $profile = $this->makeProfile([
            'has_overstay' => true,
        ]);

        $blockScores = [
            'F' => 50, 'E' => 50, 'FM' => 50, 'A' => 50, 'T' => 0, 'P' => 50, 'G' => 50,
        ];

        $result = $this->engine->generate($profile, 'US', $blockScores);

        $fields = array_column($result['recommendations'], 'field');
        $this->assertContains('has_overstay', $fields);
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
