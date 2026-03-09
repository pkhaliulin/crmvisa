<?php

namespace Tests\Integration\Rls;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantRlsTest extends RlsTestCase
{
    // -------------------------------------------------------------------------
    // Изоляция клиентов между тенантами
    // -------------------------------------------------------------------------

    public function test_tenant_context_filters_clients_by_agency(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        $this->setSuperadminContext();

        Client::factory()->create([
            'agency_id' => $agency1->id,
            'name' => 'Client A',
        ]);
        Client::factory()->create([
            'agency_id' => $agency2->id,
            'name' => 'Client B',
        ]);

        // Контекст agency 1 — видим только своих клиентов
        $this->setTenantContext($agency1->id);

        $results = $this->rawQuery('SELECT * FROM clients WHERE deleted_at IS NULL');

        $this->assertCount(1, $results);
        $this->assertEquals('Client A', $results[0]->name);
    }

    // -------------------------------------------------------------------------
    // Изоляция заявок между тенантами
    // -------------------------------------------------------------------------

    public function test_tenant_context_filters_cases_by_agency(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        $this->setSuperadminContext();

        $client1 = Client::factory()->create(['agency_id' => $agency1->id]);
        $client2 = Client::factory()->create(['agency_id' => $agency2->id]);

        VisaCase::factory()->create([
            'agency_id' => $agency1->id,
            'client_id' => $client1->id,
            'country_code' => 'DE',
        ]);
        VisaCase::factory()->create([
            'agency_id' => $agency2->id,
            'client_id' => $client2->id,
            'country_code' => 'FR',
        ]);

        // Контекст agency 2 — видим только FR
        $this->setTenantContext($agency2->id);

        $results = $this->rawQuery('SELECT * FROM cases WHERE deleted_at IS NULL');

        $this->assertCount(1, $results);
        $this->assertEquals('FR', $results[0]->country_code);
    }

    // -------------------------------------------------------------------------
    // Raw SQL с явным agency_id чужого тенанта — RLS блокирует
    // -------------------------------------------------------------------------

    public function test_raw_sql_cannot_access_other_tenant_data(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        $this->setSuperadminContext();

        Client::factory()->create([
            'agency_id' => $agency1->id,
            'name' => 'Secret Client',
        ]);

        // Контекст agency 2 — пытаемся запросить данные agency 1
        $this->setTenantContext($agency2->id);

        $results = $this->rawQuery(
            'SELECT * FROM clients WHERE agency_id = ? AND deleted_at IS NULL',
            [$agency1->id]
        );

        // RLS возвращает 0 строк, не ошибку
        $this->assertCount(0, $results);
    }

    // -------------------------------------------------------------------------
    // Суперадмин видит все данные
    // -------------------------------------------------------------------------

    public function test_superadmin_context_sees_all_data(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        $this->setSuperadminContext();

        Client::factory()->create(['agency_id' => $agency1->id]);
        Client::factory()->create(['agency_id' => $agency2->id]);

        // Суперадмин видит всех
        $results = $this->rawQuery('SELECT * FROM clients WHERE deleted_at IS NULL');
        $this->assertCount(2, $results);
    }

    // -------------------------------------------------------------------------
    // Без контекста — пустой результат
    // -------------------------------------------------------------------------

    public function test_no_context_set_returns_empty(): void
    {
        $agency = $this->createAgencyDirect('Agency 1');

        $this->setSuperadminContext();
        Client::factory()->create(['agency_id' => $agency->id]);

        // Сбрасываем весь контекст
        $this->clearContext();

        $results = $this->rawQuery('SELECT * FROM clients WHERE deleted_at IS NULL');
        $this->assertCount(0, $results);
    }

    // -------------------------------------------------------------------------
    // INSERT с чужим agency_id — RLS блокирует
    // -------------------------------------------------------------------------

    public function test_insert_with_wrong_tenant_context_blocked(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        // Контекст agency 1 — пытаемся вставить запись для agency 2
        $this->setTenantContext($agency1->id);

        $this->expectException(\Exception::class);

        DB::insert(
            'INSERT INTO clients (id, agency_id, name, email, phone, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [(string) Str::uuid(), $agency2->id, 'Hacker Client', 'hack@test.com', '+998900000000']
        );
    }

    // -------------------------------------------------------------------------
    // UPDATE чужих записей — RLS не находит строку
    // -------------------------------------------------------------------------

    public function test_update_other_tenant_data_has_no_effect(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        $this->setSuperadminContext();

        $client = Client::factory()->create([
            'agency_id' => $agency1->id,
            'name' => 'Original Name',
        ]);

        // Контекст agency 2 — пытаемся обновить клиента agency 1
        $this->setTenantContext($agency2->id);

        $affected = DB::update(
            'UPDATE clients SET name = ? WHERE id = ?',
            ['Hacked Name', $client->id]
        );

        // RLS не видит строку — 0 затронутых
        $this->assertEquals(0, $affected);

        // Проверяем что имя не изменилось
        $this->setSuperadminContext();
        $results = $this->rawQuery('SELECT name FROM clients WHERE id = ?', [$client->id]);
        $this->assertEquals('Original Name', $results[0]->name);
    }

    // -------------------------------------------------------------------------
    // DELETE чужих записей — RLS не находит строку
    // -------------------------------------------------------------------------

    public function test_delete_other_tenant_data_has_no_effect(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        $this->setSuperadminContext();

        $client = Client::factory()->create([
            'agency_id' => $agency1->id,
        ]);

        // Контекст agency 2 — пытаемся удалить клиента agency 1
        $this->setTenantContext($agency2->id);

        $affected = DB::delete('DELETE FROM clients WHERE id = ?', [$client->id]);

        $this->assertEquals(0, $affected);

        // Запись по-прежнему существует
        $this->setSuperadminContext();
        $results = $this->rawQuery('SELECT id FROM clients WHERE id = ?', [$client->id]);
        $this->assertCount(1, $results);
    }

    // -------------------------------------------------------------------------
    // Переключение контекста между тенантами
    // -------------------------------------------------------------------------

    public function test_switching_tenant_context_changes_visible_data(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        $this->setSuperadminContext();

        Client::factory()->count(3)->create(['agency_id' => $agency1->id]);
        Client::factory()->count(2)->create(['agency_id' => $agency2->id]);

        // Контекст agency 1 — 3 клиента
        $this->setTenantContext($agency1->id);
        $results1 = $this->rawQuery('SELECT * FROM clients WHERE deleted_at IS NULL');
        $this->assertCount(3, $results1);

        // Переключаемся на agency 2 — 2 клиента
        $this->setTenantContext($agency2->id);
        $results2 = $this->rawQuery('SELECT * FROM clients WHERE deleted_at IS NULL');
        $this->assertCount(2, $results2);
    }

    // -------------------------------------------------------------------------
    // Тенант может INSERT и SELECT свои данные
    // -------------------------------------------------------------------------

    public function test_tenant_can_insert_and_read_own_data(): void
    {
        $agency = $this->createAgencyDirect('My Agency');

        $this->setTenantContext($agency->id);

        $id = (string) Str::uuid();
        DB::insert(
            'INSERT INTO clients (id, agency_id, name, email, phone, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())',
            [$id, $agency->id, 'My Client', 'my@test.com', '+998901234567']
        );

        $results = $this->rawQuery('SELECT * FROM clients WHERE id = ?', [$id]);

        $this->assertCount(1, $results);
        $this->assertEquals('My Client', $results[0]->name);
    }

    // -------------------------------------------------------------------------
    // Пользователи (users) тоже изолированы по agency_id
    // -------------------------------------------------------------------------

    public function test_users_table_isolated_by_tenant(): void
    {
        $agency1 = $this->createAgencyDirect('Agency 1');
        $agency2 = $this->createAgencyDirect('Agency 2');

        $this->setSuperadminContext();

        \App\Modules\User\Models\User::factory()->create([
            'agency_id' => $agency1->id,
            'name' => 'User A',
        ]);
        \App\Modules\User\Models\User::factory()->create([
            'agency_id' => $agency2->id,
            'name' => 'User B',
        ]);

        $this->setTenantContext($agency1->id);

        $results = $this->rawQuery('SELECT * FROM users WHERE deleted_at IS NULL');

        $this->assertCount(1, $results);
        $this->assertEquals('User A', $results[0]->name);
    }
}
