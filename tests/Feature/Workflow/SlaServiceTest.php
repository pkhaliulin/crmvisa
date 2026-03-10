<?php

namespace Tests\Feature\Workflow;

use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Owner\Models\CountryVisaTypeSetting;
use App\Modules\Workflow\Models\SlaRule;
use App\Modules\Workflow\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SlaServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlaService();
    }

    // -------------------------------------------------------------------------
    // calculateCriticalDateFromTravel
    // -------------------------------------------------------------------------

    public function test_calculate_critical_date_from_travel_subtracts_processing_days(): void
    {
        DB::table('portal_countries')->insert([
            'country_code'              => 'DE',
            'name'                      => 'Germany',
            'is_active'                 => true,
            'processing_days_standard'  => 10,
            'appointment_wait_days'     => 5,
            'buffer_days_recommended'   => 7,
        ]);

        $travelDate = Carbon::parse('2026-06-01');

        $result = $this->service->calculateCriticalDateFromTravel('DE', $travelDate);

        // 10 + 5 + 7 = 22 дня до вылета
        $expected = $travelDate->copy()->subDays(22);
        $this->assertTrue($result->isSameDay($expected));
    }

    public function test_calculate_critical_date_from_travel_returns_null_if_no_country(): void
    {
        $travelDate = Carbon::parse('2026-06-01');

        $result = $this->service->calculateCriticalDateFromTravel('XX', $travelDate);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // calculateCriticalDateFromTravelEnhanced
    // -------------------------------------------------------------------------

    public function test_calculate_critical_date_enhanced_uses_country_visa_type_setting(): void
    {
        // Создаем CountryVisaTypeSetting — booted() автоматически вычислит recommended_days_before_departure
        CountryVisaTypeSetting::create([
            'country_code'        => 'DE',
            'visa_type'           => 'tourist',
            'preparation_days'    => 5,
            'appointment_wait_days' => 10,
            'processing_days_min' => 5,
            'processing_days_max' => 20,
            'processing_days_avg' => 12,
            'buffer_days'         => 7,
            'is_active'           => true,
        ]);

        $travelDate = Carbon::parse('2026-07-01');

        $result = $this->service->calculateCriticalDateFromTravelEnhanced('DE', 'tourist', $travelDate);

        // recommended_days_before_departure = preparation(5) + appointment_wait(10) + processing_max(20) + buffer(7) = 42
        $expected = $travelDate->copy()->subDays(42);
        $this->assertNotNull($result);
        $this->assertTrue($result->isSameDay($expected));
    }

    // -------------------------------------------------------------------------
    // calculateCriticalDate
    // -------------------------------------------------------------------------

    public function test_calculate_critical_date_uses_sla_rule_max_days(): void
    {
        SlaRule::create([
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
            'min_days'     => 10,
            'max_days'     => 30,
            'warning_days' => 5,
            'is_active'    => true,
        ]);

        $from = Carbon::parse('2026-04-01');
        $expected = Carbon::parse('2026-05-01'); // 2026-04-01 + 30 дней

        $result = $this->service->calculateCriticalDate('DE', 'tourist', $from);
        $this->assertNotNull($result);
        $this->assertTrue($result->isSameDay($expected));
    }

    public function test_calculate_critical_date_returns_null_if_no_rule(): void
    {
        $result = $this->service->calculateCriticalDate('XX', 'unknown');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // applyStageSla
    // -------------------------------------------------------------------------

    public function test_apply_stage_sla_sets_sla_due_at_from_config(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);

        $case = VisaCase::factory()->create([
            'agency_id'    => $agency->id,
            'client_id'    => $client->id,
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
            'stage'        => 'lead',
        ]);

        $caseStage = CaseStage::create([
            'case_id'    => $case->id,
            'stage'      => 'lead',
            'entered_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00'));

        $this->service->applyStageSla($caseStage, $case);

        $caseStage->refresh();
        // config stages.lead.sla_hours = 1
        $this->assertNotNull($caseStage->sla_due_at);
        $expectedMin = Carbon::parse('2026-04-01 12:50:00');
        $expectedMax = Carbon::parse('2026-04-01 13:10:00');
        $this->assertTrue(
            $caseStage->sla_due_at->between($expectedMin, $expectedMax),
            "sla_due_at ({$caseStage->sla_due_at}) should be ~1 hour from now"
        );

        Carbon::setTestNow();
    }

    public function test_apply_stage_sla_uses_sla_rule_fallback(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);

        $case = VisaCase::factory()->create([
            'agency_id'    => $agency->id,
            'client_id'    => $client->id,
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
            'stage'        => 'review',
        ]);

        SlaRule::create([
            'country_code'  => 'DE',
            'visa_type'     => 'tourist',
            'min_days'      => 10,
            'max_days'      => 30,
            'warning_days'  => 5,
            'is_active'     => true,
            'stage_sla_days' => ['review' => 14],
        ]);

        // config stages.review.sla_hours = null, поэтому fallback на SlaRule
        $caseStage = CaseStage::create([
            'case_id'    => $case->id,
            'stage'      => 'review',
            'entered_at' => now(),
        ]);

        // 2026-04-01 среда 12:00 UTC = 17:00 Ташкент (рабочее время)
        Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00'));

        $this->service->applyStageSla($caseStage, $case);

        $caseStage->refresh();
        $this->assertNotNull($caseStage->sla_due_at);
        // 14 stage_sla_days * 9 рабочих часов = 126 бизнес-часов
        // С учетом выходных: 14 рабочих дней от среды 01.04 = вторник 21.04
        $this->assertTrue(
            $caseStage->sla_due_at->greaterThan(Carbon::parse('2026-04-15 12:00:00')),
            "sla_due_at ({$caseStage->sla_due_at}) should be later than calendar 14 days (accounts for weekends)"
        );

        Carbon::setTestNow();
    }

    public function test_apply_stage_sla_no_rule_no_sla_due_at(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);

        $case = VisaCase::factory()->create([
            'agency_id'    => $agency->id,
            'client_id'    => $client->id,
            'country_code' => 'XX',
            'visa_type'    => 'unknown',
            'stage'        => 'review',
        ]);

        $caseStage = CaseStage::create([
            'case_id'    => $case->id,
            'stage'      => 'review',
            'entered_at' => now(),
        ]);

        $this->service->applyStageSla($caseStage, $case);

        $caseStage->refresh();
        $this->assertNull($caseStage->sla_due_at);
    }

    // -------------------------------------------------------------------------
    // markOverdueStages
    // -------------------------------------------------------------------------

    public function test_mark_overdue_stages_marks_past_due(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);

        $case = VisaCase::factory()->create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'stage'     => 'documents',
        ]);

        // Просроченный этап (sla_due_at в прошлом, ещё не закрыт)
        $overdue = CaseStage::create([
            'case_id'    => $case->id,
            'stage'      => 'documents',
            'entered_at' => now()->subDays(5),
            'exited_at'  => null,
            'sla_due_at' => now()->subHours(2),
            'is_overdue' => false,
        ]);

        // Непросроченный этап (sla_due_at в будущем)
        $onTime = CaseStage::create([
            'case_id'    => $case->id,
            'stage'      => 'qualification',
            'entered_at' => now()->subDays(1),
            'exited_at'  => null,
            'sla_due_at' => now()->addDays(2),
            'is_overdue' => false,
        ]);

        // Закрытый этап (exited_at не null) — не должен помечаться
        $closed = CaseStage::create([
            'case_id'    => $case->id,
            'stage'      => 'lead',
            'entered_at' => now()->subDays(10),
            'exited_at'  => now()->subDays(5),
            'sla_due_at' => now()->subDays(3),
            'is_overdue' => false,
        ]);

        $count = $this->service->markOverdueStages();

        $this->assertEquals(1, $count);

        $overdue->refresh();
        $onTime->refresh();
        $closed->refresh();

        $this->assertTrue($overdue->is_overdue);
        $this->assertFalse($onTime->is_overdue);
        $this->assertFalse($closed->is_overdue);
    }

    // -------------------------------------------------------------------------
    // findCasesApproachingDeadline
    // -------------------------------------------------------------------------

    public function test_find_cases_approaching_deadline_within_warning_window(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner(['is_active' => true]);
        $client = $this->createClient($agency);

        SlaRule::create([
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
            'min_days'     => 10,
            'max_days'     => 30,
            'warning_days' => 5,
            'is_active'    => true,
        ]);

        // Заявка в пределах warning window (3 дня до дедлайна, warning_days=5)
        $approaching = VisaCase::factory()->create([
            'agency_id'     => $agency->id,
            'client_id'     => $client->id,
            'country_code'  => 'DE',
            'visa_type'     => 'tourist',
            'stage'         => 'documents',
            'critical_date' => now()->addDays(3),
        ]);

        // Заявка далеко от дедлайна (20 дней)
        $farAway = VisaCase::factory()->create([
            'agency_id'     => $agency->id,
            'client_id'     => $client->id,
            'country_code'  => 'DE',
            'visa_type'     => 'tourist',
            'stage'         => 'documents',
            'critical_date' => now()->addDays(20),
        ]);

        // Заявка на этапе result — не должна попадать
        $completed = VisaCase::factory()->create([
            'agency_id'     => $agency->id,
            'client_id'     => $client->id,
            'country_code'  => 'DE',
            'visa_type'     => 'tourist',
            'stage'         => 'result',
            'critical_date' => now()->addDays(2),
        ]);

        // Заявка без critical_date — не должна попадать
        $noCritical = VisaCase::factory()->create([
            'agency_id'     => $agency->id,
            'client_id'     => $client->id,
            'country_code'  => 'DE',
            'visa_type'     => 'tourist',
            'stage'         => 'documents',
            'critical_date' => null,
        ]);

        $result = $this->service->findCasesApproachingDeadline();

        $resultIds = $result->pluck('id')->toArray();

        $this->assertContains($approaching->id, $resultIds);
        $this->assertNotContains($farAway->id, $resultIds);
        $this->assertNotContains($completed->id, $resultIds);
        $this->assertNotContains($noCritical->id, $resultIds);
    }
}
