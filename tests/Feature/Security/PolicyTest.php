<?php

namespace Tests\Feature\Security;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    private $agency;
    private $owner;
    private $manager;
    private $otherAgency;
    private $otherOwner;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->agency, $this->owner] = $this->createAgencyWithOwner();
        $this->manager = $this->createManager($this->agency);
        [$this->otherAgency, $this->otherOwner] = $this->createAgencyWithOwner();
    }

    // ===== CasePolicy =====

    public function test_owner_can_view_any_agency_case(): void
    {
        $client = $this->createClient($this->agency);
        $case = VisaCase::create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'case_number' => 'T-001',
            'country_code' => 'ES',
            'visa_type' => 'tourist',
            'stage' => 'lead',
            'public_status' => 'submitted',
            'assigned_to' => $this->manager->id,
        ]);

        $response = $this->getJson(
            "/api/v1/cases/{$case->id}",
            $this->authHeaders($this->owner)
        );

        $response->assertOk();
    }

    public function test_manager_can_view_assigned_case(): void
    {
        $client = $this->createClient($this->agency);
        $case = VisaCase::create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'case_number' => 'T-002',
            'country_code' => 'ES',
            'visa_type' => 'tourist',
            'stage' => 'lead',
            'public_status' => 'submitted',
            'assigned_to' => $this->manager->id,
        ]);

        $response = $this->getJson(
            "/api/v1/cases/{$case->id}",
            $this->authHeaders($this->manager)
        );

        $response->assertOk();
    }

    public function test_manager_cannot_view_unassigned_case(): void
    {
        $client = $this->createClient($this->agency);
        $case = VisaCase::create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'case_number' => 'T-003',
            'country_code' => 'ES',
            'visa_type' => 'tourist',
            'stage' => 'lead',
            'public_status' => 'submitted',
            'assigned_to' => $this->owner->id,
        ]);

        $response = $this->getJson(
            "/api/v1/cases/{$case->id}",
            $this->authHeaders($this->manager)
        );

        $response->assertForbidden();
    }

    public function test_manager_cannot_cancel_case(): void
    {
        $client = $this->createClient($this->agency);
        $case = VisaCase::create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'case_number' => 'T-004',
            'country_code' => 'ES',
            'visa_type' => 'tourist',
            'stage' => 'lead',
            'public_status' => 'submitted',
            'assigned_to' => $this->manager->id,
        ]);

        $response = $this->postJson(
            "/api/v1/cases/{$case->id}/cancel",
            ['reason' => 'test'],
            $this->authHeaders($this->manager)
        );

        $response->assertForbidden();
    }

    public function test_owner_can_cancel_case(): void
    {
        $client = $this->createClient($this->agency);
        $case = VisaCase::create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'case_number' => 'T-005',
            'country_code' => 'ES',
            'visa_type' => 'tourist',
            'stage' => 'lead',
            'public_status' => 'submitted',
            'assigned_to' => $this->manager->id,
        ]);

        $response = $this->postJson(
            "/api/v1/cases/{$case->id}/cancel",
            ['reason' => 'test'],
            $this->authHeaders($this->owner)
        );

        $response->assertOk();
    }

    public function test_manager_can_move_stage_on_assigned_case(): void
    {
        $client = $this->createClient($this->agency);
        $case = VisaCase::create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'case_number' => 'T-006',
            'country_code' => 'ES',
            'visa_type' => 'tourist',
            'stage' => 'lead',
            'public_status' => 'submitted',
            'assigned_to' => $this->manager->id,
        ]);

        $response = $this->postJson(
            "/api/v1/cases/{$case->id}/move-stage",
            ['stage' => 'qualification'],
            $this->authHeaders($this->manager)
        );

        $response->assertOk();
    }

    public function test_manager_cannot_delete_case(): void
    {
        $client = $this->createClient($this->agency);
        $case = VisaCase::create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'case_number' => 'T-007',
            'country_code' => 'ES',
            'visa_type' => 'tourist',
            'stage' => 'lead',
            'public_status' => 'submitted',
            'assigned_to' => $this->manager->id,
        ]);

        $response = $this->deleteJson(
            "/api/v1/cases/{$case->id}",
            [],
            $this->authHeaders($this->manager)
        );

        $response->assertForbidden();
    }

    // ===== ClientPolicy =====

    public function test_owner_can_delete_client(): void
    {
        $client = $this->createClient($this->agency);

        $response = $this->deleteJson(
            "/api/v1/clients/{$client->id}",
            [],
            $this->authHeaders($this->owner)
        );

        $response->assertOk();
    }

    public function test_manager_cannot_delete_client(): void
    {
        $client = $this->createClient($this->agency);

        $response = $this->deleteJson(
            "/api/v1/clients/{$client->id}",
            [],
            $this->authHeaders($this->manager)
        );

        $response->assertForbidden();
    }

    public function test_manager_can_update_client_with_assigned_case(): void
    {
        $client = $this->createClient($this->agency);

        VisaCase::create([
            'agency_id' => $this->agency->id,
            'client_id' => $client->id,
            'case_number' => 'T-008',
            'country_code' => 'ES',
            'visa_type' => 'tourist',
            'stage' => 'lead',
            'public_status' => 'submitted',
            'assigned_to' => $this->manager->id,
        ]);

        $response = $this->patchJson(
            "/api/v1/clients/{$client->id}",
            ['name' => 'Updated Name'],
            $this->authHeaders($this->manager)
        );

        $response->assertOk();
    }

    public function test_manager_cannot_update_client_without_assigned_case(): void
    {
        $client = $this->createClient($this->agency);

        $response = $this->patchJson(
            "/api/v1/clients/{$client->id}",
            ['name' => 'Updated Name'],
            $this->authHeaders($this->manager)
        );

        $response->assertForbidden();
    }

    // ===== UserPolicy =====

    public function test_owner_can_delete_manager(): void
    {
        $response = $this->deleteJson(
            "/api/v1/users/{$this->manager->id}",
            [],
            $this->authHeaders($this->owner)
        );

        $response->assertOk();
    }

    public function test_owner_cannot_delete_self(): void
    {
        $response = $this->deleteJson(
            "/api/v1/users/{$this->owner->id}",
            [],
            $this->authHeaders($this->owner)
        );

        $response->assertForbidden();
    }
}
