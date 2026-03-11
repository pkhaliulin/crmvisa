<?php

namespace Tests\Feature\Api;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use App\Modules\Client\Models\Client;
use App\Modules\Document\Services\ChecklistService;
use App\Modules\User\Models\User;
use App\Modules\Workflow\Services\SlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $owner;
    private User $manager;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->agency, $this->owner] = $this->createAgencyWithOwner();
        $this->manager = $this->createManager($this->agency);
        $this->client = $this->createClient($this->agency);

        // Seed portal_visa_types so validation passes (exists:portal_visa_types,slug)
        DB::table('portal_visa_types')->insertOrIgnore([
            'slug'       => 'tourist',
            'name_ru'    => 'Туристическая виза',
            'sort_order' => 1,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed reference_items for source validation (ReferenceExists rule)
        DB::table('reference_items')->insertOrIgnore([
            'id'         => (string) \Illuminate\Support\Str::uuid(),
            'category'   => 'lead_source',
            'code'       => 'direct',
            'label_ru'   => 'Прямой',
            'is_active'  => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =========================================================================
    // Clients API
    // =========================================================================

    public function test_clients_index_returns_paginated_list(): void
    {
        $this->createClient($this->agency, ['name' => 'Alice']);
        $this->createClient($this->agency, ['name' => 'Bob']);

        $response = $this->getJson('/api/v1/clients', $this->authHeaders($this->owner));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        // At least 3 clients: the one from setUp + Alice + Bob
        $this->assertGreaterThanOrEqual(3, $response->json('meta.total'));
    }

    public function test_clients_store_creates_client(): void
    {
        $payload = [
            'name'        => 'New Client',
            'phone'       => '+998901112233',
            'email'       => 'newclient@test.com',
            'nationality' => 'UZB',
            'source'      => 'direct',
        ];

        $response = $this->postJson('/api/v1/clients', $payload, $this->authHeaders($this->owner));

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'New Client');
        $response->assertJsonPath('data.nationality', 'UZB');
    }

    public function test_clients_store_rejects_invalid_nationality(): void
    {
        $payload = [
            'name'        => 'Bad Nationality',
            'phone'       => '+998900001122',
            'nationality' => 'XX', // size must be 3
        ];

        $response = $this->postJson('/api/v1/clients', $payload, $this->authHeaders($this->owner));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('nationality');
    }

    public function test_clients_show_returns_client_with_cases(): void
    {
        $response = $this->getJson(
            "/api/v1/clients/{$this->client->id}",
            $this->authHeaders($this->owner)
        );

        $response->assertOk();
        $response->assertJsonPath('data.id', $this->client->id);
        $response->assertJsonStructure(['data' => ['id', 'name', 'cases']]);
    }

    public function test_clients_destroy_soft_deletes(): void
    {
        $clientToDelete = $this->createClient($this->agency, ['name' => 'Will Be Deleted']);

        $response = $this->deleteJson(
            "/api/v1/clients/{$clientToDelete->id}",
            [],
            $this->authHeaders($this->owner)
        );

        $response->assertOk();

        // Soft deleted: not found via normal query
        $this->actingAsUser($this->owner);
        $this->assertNull(Client::find($clientToDelete->id));

        // But exists with trashed
        $this->assertNotNull(Client::withTrashed()->find($clientToDelete->id));
    }

    // =========================================================================
    // Cases API (avoiding index which uses PostgreSQL-specific SQL)
    // =========================================================================

    public function test_cases_store_creates_case(): void
    {
        // Mock services that use PostgreSQL-specific queries
        $this->mockCaseDependencies();

        $payload = [
            'client_id'    => $this->client->id,
            'country_code' => 'DE',
            'visa_type'    => 'tourist',
            'priority'     => 'normal',
        ];

        $response = $this->postJson('/api/v1/cases', $payload, $this->authHeaders($this->owner));

        $response->assertStatus(201);
        $response->assertJsonPath('data.country_code', 'DE');
        $response->assertJsonPath('data.visa_type', 'tourist');
    }

    public function test_cases_store_rejects_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/cases', [], $this->authHeaders($this->owner));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id', 'country_code', 'visa_type']);
    }

    public function test_cases_move_stage_valid_transition(): void
    {
        $this->mockCaseDependencies();

        $case = $this->createCase(['stage' => 'lead', 'public_status' => 'submitted', 'assigned_to' => $this->owner->id]);

        $response = $this->postJson(
            "/api/v1/cases/{$case->id}/move-stage",
            ['stage' => 'qualification'],
            $this->authHeaders($this->owner)
        );

        $response->assertOk();
    }

    public function test_cases_move_stage_invalid_transition_returns_422(): void
    {
        $this->mockCaseDependencies();

        $case = $this->createCase(['stage' => 'lead', 'public_status' => 'submitted']);

        // lead -> ready is not allowed (must go through qualification, documents, etc.)
        $response = $this->postJson(
            "/api/v1/cases/{$case->id}/move-stage",
            ['stage' => 'ready'],
            $this->authHeaders($this->owner)
        );

        $response->assertStatus(422);
    }

    public function test_cases_cancel_by_owner(): void
    {
        $this->mockCaseDependencies();

        $case = $this->createCase(['stage' => 'lead', 'public_status' => 'submitted']);

        $response = $this->postJson(
            "/api/v1/cases/{$case->id}/cancel",
            ['reason' => 'Client changed mind'],
            $this->authHeaders($this->owner)
        );

        $response->assertOk();

        $case->refresh();
        $this->assertEquals('cancelled', $case->public_status);
    }

    // =========================================================================
    // Middleware: CheckRole
    // =========================================================================

    public function test_manager_cannot_access_owner_only_routes(): void
    {
        // POST /users requires role:owner,superadmin
        $response = $this->postJson('/api/v1/users', [
            'name'     => 'New Manager',
            'email'    => 'new@test.com',
            'password' => 'Password123!',
            'role'     => 'manager',
        ], $this->authHeaders($this->manager));

        $response->assertStatus(403);
    }

    // =========================================================================
    // Middleware: CheckAgencyPlan (expired plan)
    // =========================================================================

    public function test_expired_plan_returns_403(): void
    {
        // Create an inactive agency (is_active=false makes isPlanActive() return false)
        $inactiveAgency = Agency::factory()->create(['is_active' => false]);
        $inactiveOwner = User::factory()->owner()->create([
            'agency_id' => $inactiveAgency->id,
        ]);

        $response = $this->getJson('/api/v1/clients', $this->authHeaders($inactiveOwner));

        $response->assertStatus(403);
    }

    // =========================================================================
    // Unauthenticated request
    // =========================================================================

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/clients');

        $response->assertUnauthorized();
    }

    // =========================================================================
    // Kanban
    // =========================================================================

    public function test_kanban_board_returns_structure(): void
    {
        // Create a case that will appear on the board (not draft/awaiting_payment/cancelled)
        $this->actingAsUser($this->owner);
        VisaCase::factory()->create([
            'agency_id'     => $this->agency->id,
            'client_id'     => $this->client->id,
            'assigned_to'   => $this->owner->id,
            'stage'         => 'qualification',
            'public_status' => 'manager_assigned',
        ]);

        $response = $this->getJson('/api/v1/kanban', $this->authHeaders($this->owner));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'board',
                'role',
                'total',
                'overdue',
                'critical',
            ],
        ]);

        // Board should contain stage columns from config/stages.php
        $board = $response->json('data.board');
        $this->assertNotEmpty($board);

        $keys = collect($board)->pluck('key')->toArray();
        $this->assertContains('lead', $keys);
        $this->assertContains('qualification', $keys);
        $this->assertContains('result', $keys);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Mock ChecklistService and SlaService to avoid PostgreSQL-specific queries.
     */
    private function mockCaseDependencies(): void
    {
        $slaService = Mockery::mock(SlaService::class);
        $slaService->shouldReceive('calculateCriticalDateFromTravel')->andReturnNull();
        $slaService->shouldReceive('calculateCriticalDateFromTravelEnhanced')->andReturnNull();
        $slaService->shouldReceive('calculateCriticalDate')->andReturnNull();
        $slaService->shouldReceive('applyStageSla')->andReturnNull();

        $checklistService = Mockery::mock(ChecklistService::class);
        $checklistService->shouldReceive('createForCase')->andReturnNull();

        $this->app->instance(SlaService::class, $slaService);
        $this->app->instance(ChecklistService::class, $checklistService);
    }

    /**
     * Create a VisaCase with required relations for the current agency.
     */
    private function createCase(array $attrs = []): VisaCase
    {
        $this->actingAsUser($this->owner);

        return VisaCase::factory()->create(array_merge([
            'agency_id' => $this->agency->id,
            'client_id' => $this->client->id,
        ], $attrs));
    }
}
