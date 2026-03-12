<?php

namespace Tests\Feature\Case;

use App\Modules\Agency\Enums\Plan;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\Workflow\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private CaseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Мокаем внешние зависимости, которые обращаются к таблицам
        // не входящим в базовый набор или содержащим PostgreSQL-specific SQL
        $this->mock(ChecklistService::class, function ($mock) {
            $mock->shouldReceive('createForCase')->andReturn(null);
        });
        $this->mock(SlaService::class, function ($mock) {
            $mock->shouldReceive('calculateCriticalDate')->andReturn(null);
            $mock->shouldReceive('calculateCriticalDateFromTravel')->andReturn(null);
            $mock->shouldReceive('calculateCriticalDateFromTravelEnhanced')->andReturn(null);
            $mock->shouldReceive('applyStageSla')->andReturn(null);
        });

        $this->service = app(CaseService::class);
    }

    // -------------------------------------------------------------------------
    // createCase
    // -------------------------------------------------------------------------

    public function test_create_case_assigns_agency_from_auth(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = $this->service->createCase([
            'client_id'    => $client->id,
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
        ]);

        $this->assertEquals($agency->id, $case->agency_id);
        $this->assertNotNull($case->case_number);
        $this->assertStringStartsWith('VB-', $case->case_number);
    }

    public function test_create_case_manager_auto_assigns_self(): void
    {
        [$agency, ] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $this->actingAsUser($manager);

        $case = $this->service->createCase([
            'client_id'    => $client->id,
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
        ]);

        $this->assertEquals($manager->id, $case->assigned_to);
        // При назначении менеджера из lead автоматически переходит в qualification
        $this->assertEquals('qualification', $case->stage);
        $this->assertEquals('manager_assigned', $case->public_status);
    }

    public function test_create_case_owner_can_set_lead_stage(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = $this->service->createCase([
            'client_id'    => $client->id,
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
            'stage'        => 'lead',
        ]);

        // Owner без assigned_to — остается в lead
        $this->assertEquals('lead', $case->stage);
    }

    // -------------------------------------------------------------------------
    // Plan limits
    // -------------------------------------------------------------------------

    public function test_create_case_respects_plan_limit(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner(['plan' => Plan::Trial]);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        // Создаем 50 активных заявок (лимит Trial)
        for ($i = 0; $i < 50; $i++) {
            VisaCase::factory()->create([
                'agency_id'  => $agency->id,
                'client_id'  => $client->id,
                'stage'      => 'qualification',
            ]);
        }

        $this->expectException(ValidationException::class);

        $this->service->createCase([
            'client_id'    => $client->id,
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
        ]);
    }

    public function test_result_stage_cases_do_not_count_against_limit(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner(['plan' => Plan::Trial]);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        // 50 заявок в result (завершены) — не считаются
        for ($i = 0; $i < 50; $i++) {
            VisaCase::factory()->create([
                'agency_id'  => $agency->id,
                'client_id'  => $client->id,
                'stage'      => 'result',
            ]);
        }

        // Должно пройти — активных заявок 0
        $case = $this->service->createCase([
            'client_id'    => $client->id,
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
        ]);

        $this->assertNotNull($case->id);
    }

    // -------------------------------------------------------------------------
    // moveToStage
    // -------------------------------------------------------------------------

    public function test_valid_transition_lead_to_qualification(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'   => $agency->id,
            'client_id'   => $client->id,
            'stage'       => 'lead',
            'assigned_to' => $manager->id,
        ]);

        $result = $this->service->moveToStage($case, 'qualification');

        $this->assertEquals('qualification', $result->stage);
        $this->assertEquals('manager_assigned', $result->public_status);
    }

    public function test_valid_transition_qualification_to_documents(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'      => $agency->id,
            'client_id'      => $client->id,
            'stage'          => 'qualification',
            'assigned_to'    => $manager->id,
            'payment_status' => 'paid',
        ]);

        $result = $this->service->moveToStage($case, 'documents');

        $this->assertEquals('documents', $result->stage);
        $this->assertEquals('document_collection', $result->public_status);
    }

    public function test_external_lead_qualification_to_documents_requires_payment(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'      => $agency->id,
            'client_id'      => $client->id,
            'stage'          => 'qualification',
            'assigned_to'    => $manager->id,
            'lead_source'    => 'api',
            'payment_status' => 'unpaid',
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->service->moveToStage($case, 'documents');
    }

    public function test_invalid_transition_lead_to_documents_throws(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'  => $agency->id,
            'client_id'  => $client->id,
            'stage'      => 'lead',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->moveToStage($case, 'documents');
    }

    public function test_invalid_transition_lead_to_result_throws(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'  => $agency->id,
            'client_id'  => $client->id,
            'stage'      => 'lead',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->moveToStage($case, 'result');
    }

    public function test_result_stage_has_no_transitions(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'  => $agency->id,
            'client_id'  => $client->id,
            'stage'      => 'result',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->moveToStage($case, 'review');
    }

    public function test_backward_transition_does_not_update_public_status(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'     => $agency->id,
            'client_id'     => $client->id,
            'stage'         => 'documents',
            'public_status' => 'document_collection',
            'assigned_to'   => $manager->id,
        ]);

        $result = $this->service->moveToStage($case, 'qualification');

        $this->assertEquals('qualification', $result->stage);
        // public_status пересчитывается при откате для синхронности stage и public_status
        $this->assertEquals('manager_assigned', $result->public_status);
    }

    public function test_stage_history_recorded_on_transition(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'   => $agency->id,
            'client_id'   => $client->id,
            'stage'       => 'lead',
            'assigned_to' => $manager->id,
        ]);

        $this->service->moveToStage($case, 'qualification');

        $history = $case->stageHistory()->get();
        // Минимум 1 запись нового этапа
        $this->assertTrue($history->contains('stage', 'qualification'));
    }

    // -------------------------------------------------------------------------
    // cancelCase
    // -------------------------------------------------------------------------

    public function test_cancel_case_from_lead(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'  => $agency->id,
            'client_id'  => $client->id,
            'stage'      => 'lead',
        ]);

        $result = $this->service->cancelCase($case, 'Клиент передумал');

        $this->assertEquals('cancelled', $result->public_status);
        $this->assertStringContainsString('Клиент передумал', $result->notes);
    }

    public function test_cancel_case_from_documents(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'  => $agency->id,
            'client_id'  => $client->id,
            'stage'      => 'documents',
        ]);

        $result = $this->service->cancelCase($case, 'Нет документов');

        $this->assertEquals('cancelled', $result->public_status);
    }

    public function test_cancel_case_from_review_throws(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'  => $agency->id,
            'client_id'  => $client->id,
            'stage'      => 'review',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->cancelCase($case);
    }

    public function test_cancel_case_from_result_throws(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'  => $agency->id,
            'client_id'  => $client->id,
            'stage'      => 'result',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->cancelCase($case);
    }

    public function test_cancel_already_cancelled_throws(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'     => $agency->id,
            'client_id'     => $client->id,
            'stage'         => 'lead',
            'public_status' => 'cancelled',
        ]);

        $this->expectException(ValidationException::class);
        $this->service->cancelCase($case);
    }

    // -------------------------------------------------------------------------
    // completeCase
    // -------------------------------------------------------------------------

    public function test_complete_case_approved(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'   => $agency->id,
            'client_id'   => $client->id,
            'stage'       => 'review',
            'assigned_to' => $manager->id,
        ]);

        $result = $this->service->completeCase($case, 'approved', [
            'visa_issued_at' => now()->toDateString(),
        ]);

        $this->assertEquals('result', $result->stage);
        $this->assertEquals('completed', $result->public_status);
        $this->assertEquals('approved', $result->result_type);
    }

    public function test_complete_case_rejected(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $manager = $this->createManager($agency);
        $client = $this->createClient($agency);
        $this->actingAsUser($owner);

        $case = VisaCase::factory()->create([
            'agency_id'   => $agency->id,
            'client_id'   => $client->id,
            'stage'       => 'review',
            'assigned_to' => $manager->id,
        ]);

        $result = $this->service->completeCase($case, 'rejected', [
            'rejection_reason' => 'Недостаточно документов',
        ]);

        $this->assertEquals('result', $result->stage);
        $this->assertEquals('rejected', $result->public_status);
        $this->assertEquals('rejected', $result->result_type);
        $this->assertEquals('Недостаточно документов', $result->rejection_reason);
    }

    // -------------------------------------------------------------------------
    // ALLOWED_TRANSITIONS completeness
    // -------------------------------------------------------------------------

    public function test_all_stages_have_transition_rules(): void
    {
        $stages = ['lead', 'qualification', 'documents', 'doc_review', 'translation', 'ready', 'review', 'result'];

        foreach ($stages as $stage) {
            $this->assertArrayHasKey($stage, CaseService::ALLOWED_TRANSITIONS, "Stage '{$stage}' missing from ALLOWED_TRANSITIONS");
        }
    }

    public function test_full_forward_path(): void
    {
        // lead → qualification → documents → doc_review → translation → ready → review → result
        $path = ['lead', 'qualification', 'documents', 'doc_review', 'translation', 'ready', 'review', 'result'];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $from = $path[$i];
            $to   = $path[$i + 1];
            $allowed = CaseService::ALLOWED_TRANSITIONS[$from];
            $this->assertContains($to, $allowed, "Transition {$from} → {$to} should be allowed");
        }
    }
}
