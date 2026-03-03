<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Таблицы маркетплейса — публичные пользователи должны видеть
     * все записи (SELECT), но изменять только свой тенант.
     */
    private array $marketplaceTables = [
        'agency_service_packages',
        'agency_work_countries',
        'agency_reviews',
        'agency_profiles',
    ];

    public function up(): void
    {
        foreach ($this->marketplaceTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table}");

            // USING (SELECT/UPDATE visible/DELETE visible):
            //   тенант + суперадмин + любой публичный пользователь (маркетплейс)
            // WITH CHECK (INSERT/UPDATE new values):
            //   только тенант + суперадмин
            DB::statement("
                CREATE POLICY tenant_isolation_{$table} ON {$table}
                FOR ALL
                USING (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR current_setting('app.is_public_user', true) = 'true'
                )
                WITH CHECK (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                )
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->marketplaceTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table}");

            DB::statement("
                CREATE POLICY tenant_isolation_{$table} ON {$table}
                FOR ALL
                USING (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                )
                WITH CHECK (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                )
            ");
        }
    }
};
