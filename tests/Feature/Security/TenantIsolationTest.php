<?php

namespace Tests\Feature\Security;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Client isolation via HasTenant
    // -------------------------------------------------------------------------

    public function test_user_sees_only_own_agency_clients(): void
    {
        [$agencyA, $ownerA] = $this->createAgencyWithOwner();
        [$agencyB, $ownerB] = $this->createAgencyWithOwner();

        $clientA = $this->createClient($agencyA, ['name' => 'Client A']);
        $clientB = $this->createClient($agencyB, ['name' => 'Client B']);

        // Логин owner A
        $this->actingAsUser($ownerA);

        $clients = Client::all();

        $this->assertTrue($clients->contains('id', $clientA->id));
        $this->assertFalse($clients->contains('id', $clientB->id));
    }

    public function test_user_sees_only_own_agency_cases(): void
    {
        [$agencyA, $ownerA] = $this->createAgencyWithOwner();
        [$agencyB, $ownerB] = $this->createAgencyWithOwner();

        $clientA = $this->createClient($agencyA);
        $clientB = $this->createClient($agencyB);

        $caseA = VisaCase::factory()->create([
            'agency_id' => $agencyA->id,
            'client_id' => $clientA->id,
        ]);
        $caseB = VisaCase::factory()->create([
            'agency_id' => $agencyB->id,
            'client_id' => $clientB->id,
        ]);

        $this->actingAsUser($ownerA);

        $cases = VisaCase::all();

        $this->assertTrue($cases->contains('id', $caseA->id));
        $this->assertFalse($cases->contains('id', $caseB->id));
    }

    // -------------------------------------------------------------------------
    // API endpoint isolation
    // -------------------------------------------------------------------------

    public function test_clients_api_returns_only_own_agency(): void
    {
        [$agencyA, $ownerA] = $this->createAgencyWithOwner();
        [$agencyB, $ownerB] = $this->createAgencyWithOwner();

        $this->createClient($agencyA, ['name' => 'My Client']);
        $this->createClient($agencyB, ['name' => 'Other Client']);

        $response = $this->getJson('/api/v1/clients', $this->authHeaders($ownerA));

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('My Client', $names);
        $this->assertNotContains('Other Client', $names);
    }

    // API-тест для cases пропущен: CaseController использует PostgreSQL-specific SQL
    // (INTERVAL, NOW()) несовместимый с SQLite. Изоляция проверяется через model-level тест выше.

    // -------------------------------------------------------------------------
    // HasTenant auto-fill on create
    // -------------------------------------------------------------------------

    public function test_has_tenant_auto_fills_agency_id(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $this->actingAsUser($owner);

        // Создаем клиента без agency_id — HasTenant должен заполнить
        $client = Client::create([
            'name'  => 'Auto Fill Test',
            'email' => 'auto@test.com',
        ]);

        $this->assertEquals($agency->id, $client->agency_id);
    }

    // -------------------------------------------------------------------------
    // withoutTenant scope
    // -------------------------------------------------------------------------

    public function test_without_tenant_scope_sees_all(): void
    {
        [$agencyA, $ownerA] = $this->createAgencyWithOwner();
        [$agencyB, $ownerB] = $this->createAgencyWithOwner();

        $this->createClient($agencyA);
        $this->createClient($agencyB);

        $this->actingAsUser($ownerA);

        // С tenant scope — только свои
        $scoped = Client::all();
        $this->assertCount(1, $scoped);

        // Без tenant scope — все
        $all = Client::withoutTenant()->get();
        $this->assertCount(2, $all);
    }

    // -------------------------------------------------------------------------
    // Cross-tenant access prevention
    // -------------------------------------------------------------------------

    public function test_cannot_access_other_agency_client_by_id(): void
    {
        [$agencyA, $ownerA] = $this->createAgencyWithOwner();
        [$agencyB, $ownerB] = $this->createAgencyWithOwner();

        $clientB = $this->createClient($agencyB);

        $this->actingAsUser($ownerA);

        // Прямой запрос по ID чужого клиента — tenant scope не найдет
        $found = Client::find($clientB->id);
        $this->assertNull($found);
    }

    public function test_manager_sees_only_own_agency_data(): void
    {
        [$agencyA, ] = $this->createAgencyWithOwner();
        $managerA = $this->createManager($agencyA);

        [$agencyB, ] = $this->createAgencyWithOwner();
        $this->createClient($agencyB, ['name' => 'Foreign Client']);
        $this->createClient($agencyA, ['name' => 'Own Client']);

        $this->actingAsUser($managerA);

        $clients = Client::all();
        $this->assertCount(1, $clients);
        $this->assertEquals('Own Client', $clients->first()->name);
    }
}
