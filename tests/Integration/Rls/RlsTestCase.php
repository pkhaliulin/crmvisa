<?php

namespace Tests\Integration\Rls;

use App\Modules\Agency\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

abstract class RlsTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Эти тесты требуют PostgreSQL с RLS-политиками
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('RLS tests require PostgreSQL');
        }
    }

    /**
     * Установить контекст тенанта для RLS (имитация middleware SetTenantContext).
     */
    protected function setTenantContext(string $agencyId): void
    {
        DB::statement("SET app.current_tenant_id = ?", [$agencyId]);
        DB::statement("SET app.is_superadmin = 'false'");
        DB::statement("SET app.is_public_user = 'false'");
    }

    /**
     * Установить контекст суперадмина — видит все данные.
     */
    protected function setSuperadminContext(): void
    {
        DB::statement("SET app.is_superadmin = 'true'");
    }

    /**
     * Установить контекст публичного пользователя.
     */
    protected function setPublicUserContext(string $publicUserId): void
    {
        DB::statement("SET app.is_public_user = 'true'");
        DB::statement("SET app.public_user_id = ?", [$publicUserId]);
    }

    /**
     * Сбросить весь RLS-контекст — ни тенант, ни суперадмин, ни публичный.
     */
    protected function clearContext(): void
    {
        DB::statement("SET app.current_tenant_id = ''");
        DB::statement("SET app.is_superadmin = 'false'");
        DB::statement("SET app.is_public_user = 'false'");
        DB::statement("SET app.public_user_id = ''");
    }

    /**
     * Выполнить raw SQL, минуя Eloquent scopes.
     */
    protected function rawQuery(string $sql, array $bindings = []): array
    {
        return DB::select($sql, $bindings);
    }

    /**
     * Создать агентство в обход RLS (через контекст суперадмина).
     */
    protected function createAgencyDirect(string $name = 'Test Agency'): Agency
    {
        DB::statement("SET app.is_superadmin = 'true'");

        $agency = Agency::factory()->create(['name' => $name]);

        DB::statement("SET app.is_superadmin = 'false'");

        return $agency;
    }
}
