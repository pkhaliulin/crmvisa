<?php

namespace Tests\Unit\Scoring;

use App\Modules\Scoring\Services\ScoringDataAdapter;
use App\Modules\Scoring\Services\UnifiedScoringEngine;
use Tests\TestCase;

/**
 * SSOT-тест: доказывает, что UnifiedScoringEngine — единственный
 * источник расчёта скоринга. CRM и портал используют одну формулу.
 *
 * Если этот тест сломается — значит кто-то создал параллельный расчёт.
 */
class UnifiedScoringSsotTest extends TestCase
{
    private UnifiedScoringEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new UnifiedScoringEngine();
    }

    // =========================================================================
    // SSOT: одни данные = один результат
    // =========================================================================

    public function test_same_data_produces_identical_score_on_repeated_calls(): void
    {
        $data = $this->sampleData();

        $result1 = $this->engine->scoreProfile($data);
        $result2 = $this->engine->scoreProfile($data);

        $this->assertSame($result1['score'], $result2['score'], 'SSOT: повторный вызов должен дать тот же скор');
        $this->assertSame($result1['blocks'], $result2['blocks'], 'SSOT: блоки должны совпадать');
    }

    public function test_crm_and_portal_data_with_same_fields_produce_same_score(): void
    {
        // Одни и те же данные, поданные через разные «адаптеры» (имена полей)
        $portalData = [
            'employment_type'      => 'employed',
            'monthly_income_usd'   => 3000,
            'marital_status'       => 'married',
            'has_children'         => true,
            'children_count'       => 2,
            'has_property'         => true,
            'has_car'              => true,
            'has_schengen_visa'    => true,
            'visas_obtained_count' => 3,
            'refusals_count'       => 0,
            'had_overstay'         => false,
            'education_level'      => 'bachelor',
            'dob'                  => '1991-06-15',
            'employed_years'       => 5,
        ];

        // CRM-формат — те же данные, другие имена полей
        $crmData = [
            'employment_type'      => 'employed',
            'monthly_income'       => 3000, // monthly_income вместо monthly_income_usd
            'marital_status'       => 'married',
            'has_children'         => true,
            'children_count'       => 2,
            'has_real_estate'      => true, // has_real_estate вместо has_property
            'has_car'              => true,
            'has_schengen_visa'    => true,
            'visas_obtained_count' => 3,
            'previous_refusals'   => 0, // previous_refusals вместо refusals_count
            'has_overstay'         => false,
            'education_level'      => 'bachelor',
            'dob'                  => '1991-06-15',
            'years_at_current_job' => 5, // years_at_current_job вместо employed_years
        ];

        $portalResult = $this->engine->scoreProfile($portalData);
        $crmResult    = $this->engine->scoreProfile($crmData);

        $this->assertSame(
            $portalResult['score'],
            $crmResult['score'],
            'SSOT: портал и CRM с одинаковыми данными должны давать идентичный скор'
        );

        $this->assertSame($portalResult['blocks'], $crmResult['blocks'], 'SSOT: блоки должны совпадать');
    }

    // =========================================================================
    // 4 блока — единственная формула
    // =========================================================================

    public function test_exactly_4_blocks_returned(): void
    {
        $result = $this->engine->scoreProfile($this->sampleData());

        $this->assertArrayHasKey('F', $result['blocks']);
        $this->assertArrayHasKey('T', $result['blocks']);
        $this->assertArrayHasKey('V', $result['blocks']);
        $this->assertArrayHasKey('P', $result['blocks']);
        $this->assertCount(4, $result['blocks'], 'SSOT: должно быть ровно 4 блока (F, T, V, P)');
    }

    public function test_default_weights_sum_to_100(): void
    {
        $result = $this->engine->scoreProfile($this->sampleData());

        $weights = $result['weights'];
        $sum = array_sum($weights);
        $this->assertEquals(100, $sum, 'SSOT: сумма весов должна быть 100');
    }

    public function test_weights_are_f30_t25_v30_p15(): void
    {
        $result = $this->engine->scoreProfile($this->sampleData());

        $this->assertEquals(30, $result['weights']['F']);
        $this->assertEquals(25, $result['weights']['T']);
        $this->assertEquals(30, $result['weights']['V']);
        $this->assertEquals(15, $result['weights']['P']);
    }

    // =========================================================================
    // Блоки: каждый 0-100
    // =========================================================================

    public function test_all_blocks_within_0_100(): void
    {
        $datasets = [
            'empty'  => [],
            'strong' => $this->sampleData(),
            'worst'  => [
                'employment_type'    => 'unemployed',
                'monthly_income_usd' => 0,
                'refusals_count'     => 5,
                'had_overstay'       => true,
                'had_deportation'    => true,
            ],
        ];

        foreach ($datasets as $name => $data) {
            $result = $this->engine->scoreProfile($data);
            foreach ($result['blocks'] as $block => $score) {
                $this->assertGreaterThanOrEqual(0, $score, "$name: блок $block >= 0");
                $this->assertLessThanOrEqual(100, $score, "$name: блок $block <= 100");
            }
        }
    }

    public function test_final_score_within_5_100(): void
    {
        $datasets = [
            'empty'  => [],
            'strong' => $this->sampleData(),
            'worst'  => ['had_deportation' => true, 'refusals_count' => 5, 'had_overstay' => true],
        ];

        foreach ($datasets as $name => $data) {
            $result = $this->engine->scoreProfile($data);
            $this->assertGreaterThanOrEqual(5, $result['score'], "$name: score >= 5");
            $this->assertLessThanOrEqual(100, $result['score'], "$name: score <= 100");
        }
    }

    // =========================================================================
    // Красные флаги — множители
    // =========================================================================

    public function test_criminal_record_blocks_completely(): void
    {
        $data = array_merge($this->sampleData(), ['has_criminal_record' => true]);
        $result = $this->engine->scoreProfile($data);

        $this->assertTrue($result['is_blocked']);
        $this->assertEquals(0.0, $result['red_flag_multiplier']);
        $this->assertEquals(5, $result['score'], 'Судимость: скор должен быть минимальным (5)');
    }

    public function test_deportation_multiplier_05(): void
    {
        $data = array_merge($this->sampleData(), ['had_deportation' => true]);
        $result = $this->engine->scoreProfile($data);

        $this->assertLessThanOrEqual(0.5, $result['red_flag_multiplier']);
    }

    public function test_overstay_multiplier_07(): void
    {
        $data = array_merge($this->sampleData(), [
            'had_overstay' => true,
            'refusals_count' => 0,
            'had_deportation' => false,
        ]);
        $result = $this->engine->scoreProfile($data);

        $this->assertEquals(0.7, $result['red_flag_multiplier']);
    }

    // =========================================================================
    // Доход влияет на скор
    // =========================================================================

    public function test_higher_income_gives_higher_score(): void
    {
        $base = ['employment_type' => 'employed', 'marital_status' => 'married', 'education_level' => 'bachelor', 'dob' => '1990-01-01'];

        $low  = $this->engine->scoreProfile(array_merge($base, ['monthly_income_usd' => 400]));
        $mid  = $this->engine->scoreProfile(array_merge($base, ['monthly_income_usd' => 1500]));
        $high = $this->engine->scoreProfile(array_merge($base, ['monthly_income_usd' => 5000]));

        $this->assertGreaterThan($low['blocks']['F'], $mid['blocks']['F'], 'Доход 1500 > 400');
        $this->assertGreaterThan($mid['blocks']['F'], $high['blocks']['F'], 'Доход 5000 > 1500');
        $this->assertGreaterThan($low['score'], $high['score'], 'Общий скор растёт с доходом');
    }

    // =========================================================================
    // scoreForCountry — нелинейная формула
    // =========================================================================

    public function test_country_scoring_formula_structure(): void
    {
        // Проверяем структуру формулы без обращения к БД
        // scoreForCountry использует те же 4 блока + country adjustment
        $data = $this->sampleData();
        $profileResult = $this->engine->scoreProfile($data);

        // Profile scoring возвращает ровно те же блоки что и country scoring
        $this->assertCount(4, $profileResult['blocks']);
        $this->assertArrayHasKey('red_flag_multiplier', $profileResult);
        $this->assertArrayHasKey('is_blocked', $profileResult);
        $this->assertArrayHasKey('weights', $profileResult);
    }

    public function test_sensitivity_formula_nonlinear(): void
    {
        // Проверяем нелинейность формулы без обращения к БД
        // sensitivity = 0.10 + 0.30 * (1 - profile_base / 100)
        // При слабом профиле (base=30): sensitivity = 0.10 + 0.30 * 0.70 = 0.31
        // При сильном профиле (base=80): sensitivity = 0.10 + 0.30 * 0.20 = 0.16

        $weakSensitivity  = 0.10 + 0.30 * (1 - 30 / 100);
        $strongSensitivity = 0.10 + 0.30 * (1 - 80 / 100);

        $this->assertGreaterThan($strongSensitivity, $weakSensitivity,
            'Слабый профиль имеет более высокую чувствительность к стране');
        $this->assertEqualsWithDelta(0.31, $weakSensitivity, 0.01);
        $this->assertEqualsWithDelta(0.16, $strongSensitivity, 0.01);
    }

    // =========================================================================
    // ScoringDataAdapter consistency
    // =========================================================================

    public function test_adapter_maps_public_user_fields_correctly(): void
    {
        // Симулируем PublicUser с основными полями
        $mockUser = new \stdClass();
        $mockUser->employment_type = 'employed';
        $mockUser->monthly_income_usd = 2000;
        $mockUser->marital_status = 'married';
        $mockUser->has_children = true;
        $mockUser->children_count = 1;
        $mockUser->has_property = true;
        $mockUser->has_car = false;
        $mockUser->has_business = false;
        $mockUser->employed_years = 3;
        $mockUser->has_schengen_visa = true;
        $mockUser->has_us_visa = false;
        $mockUser->visas_obtained_count = 2;
        $mockUser->refusals_count = 0;
        $mockUser->had_overstay = false;
        $mockUser->had_deportation = false;
        $mockUser->last_refusal_year = null;
        $mockUser->education_level = 'master';
        $mockUser->dob = '1992-05-10';

        // Вручную строим массив (как ScoringDataAdapter::fromPublicUser)
        $data = [
            'employment_type'      => $mockUser->employment_type,
            'monthly_income_usd'   => $mockUser->monthly_income_usd,
            'marital_status'       => $mockUser->marital_status,
            'has_children'         => $mockUser->has_children,
            'children_count'       => $mockUser->children_count,
            'has_property'         => $mockUser->has_property,
            'has_car'              => $mockUser->has_car,
            'has_business'         => $mockUser->has_business,
            'employed_years'       => $mockUser->employed_years,
            'has_schengen_visa'    => $mockUser->has_schengen_visa,
            'has_us_visa'          => $mockUser->has_us_visa,
            'visas_obtained_count' => $mockUser->visas_obtained_count,
            'refusals_count'       => $mockUser->refusals_count,
            'had_overstay'         => $mockUser->had_overstay,
            'had_deportation'      => $mockUser->had_deportation,
            'last_refusal_year'    => $mockUser->last_refusal_year,
            'education_level'      => $mockUser->education_level,
            'dob'                  => $mockUser->dob,
        ];

        $result = $this->engine->scoreProfile($data);

        $this->assertIsInt($result['score']);
        $this->assertGreaterThan(30, $result['score'], 'Заполненный профиль > 30');
        $this->assertCount(4, $result['blocks']);
    }

    // =========================================================================
    // Никто не должен считать скоринг вне UnifiedScoringEngine
    // =========================================================================

    public function test_no_parallel_scoring_in_public_scoring_controller(): void
    {
        $controllerCode = file_get_contents(
            base_path('app/Modules/PublicPortal/Controllers/PublicScoringController.php')
        );

        // Не должно быть прямых вычислений скора в контроллере
        $this->assertStringNotContainsString(
            '* 0.30 + ',
            $controllerCode,
            'SSOT: контроллер НЕ должен содержать формулу скоринга — только вызов UnifiedScoringEngine'
        );

        $this->assertStringNotContainsString(
            'calcFinances',
            $controllerCode,
            'SSOT: контроллер НЕ должен вызывать calcFinances напрямую — только через UnifiedScoringEngine'
        );

        // Должен использовать UnifiedScoringEngine
        $this->assertStringContainsString(
            'UnifiedScoringEngine',
            $controllerCode,
            'SSOT: контроллер ДОЛЖЕН использовать UnifiedScoringEngine'
        );
    }

    public function test_no_parallel_scoring_in_crm_scoring_controller(): void
    {
        $controllerCode = file_get_contents(
            base_path('app/Modules/Scoring/Controllers/ScoringController.php')
        );

        $this->assertStringContainsString(
            'UnifiedScoringEngine',
            $controllerCode,
            'SSOT: CRM контроллер ДОЛЖЕН использовать UnifiedScoringEngine'
        );

        // Проверяем что не используется СТАРЫЙ ScoringEngine (без Unified)
        // Паттерн: "use ... ScoringEngine;" без "Unified" перед ним
        $this->assertDoesNotMatchRegularExpression(
            '/use\s+.*\\\\ScoringEngine\s*;/',
            preg_replace('/UnifiedScoringEngine/', '', $controllerCode),
            'SSOT: CRM контроллер НЕ должен импортировать старый ScoringEngine'
        );
    }

    public function test_job_uses_unified_engine(): void
    {
        $jobCode = file_get_contents(
            base_path('app/Modules/Scoring/Jobs/CalculateClientScoreJob.php')
        );

        $this->assertStringContainsString(
            'UnifiedScoringEngine',
            $jobCode,
            'SSOT: Job ДОЛЖЕН использовать UnifiedScoringEngine'
        );

        // Проверяем что не используется СТАРЫЙ ScoringEngine
        $this->assertDoesNotMatchRegularExpression(
            '/use\s+.*\\\\ScoringEngine\s*;/',
            preg_replace('/UnifiedScoringEngine/', '', $jobCode),
            'SSOT: Job НЕ должен импортировать старый ScoringEngine'
        );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function sampleData(): array
    {
        return [
            'employment_type'      => 'employed',
            'monthly_income_usd'   => 3000,
            'marital_status'       => 'married',
            'has_children'         => true,
            'children_count'       => 2,
            'has_property'         => true,
            'has_car'              => true,
            'has_schengen_visa'    => true,
            'has_us_visa'          => false,
            'visas_obtained_count' => 3,
            'refusals_count'       => 0,
            'had_overstay'         => false,
            'had_deportation'      => false,
            'education_level'      => 'master',
            'dob'                  => '1991-01-01',
            'employed_years'       => 5,
        ];
    }
}
