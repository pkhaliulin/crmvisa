<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Таблицы, где публичный портал может создавать записи с agency_id = NULL.
     */
    private array $publicTables = ['clients', 'cases', 'documents', 'case_checklist'];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            foreach ($this->publicTables as $table) {
                // Удаляем старую политику
                DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table}");

                // Новая политика: tenant + superadmin + public user (agency_id IS NULL)
                DB::statement("
                    CREATE POLICY tenant_isolation_{$table} ON {$table}
                    FOR ALL
                    USING (
                        agency_id::text = current_setting('app.current_tenant_id', true)
                        OR current_setting('app.is_superadmin', true) = 'true'
                        OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    )
                    WITH CHECK (
                        agency_id::text = current_setting('app.current_tenant_id', true)
                        OR current_setting('app.is_superadmin', true) = 'true'
                        OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    )
                ");
            }
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            foreach ($this->publicTables as $table) {
                DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table}");

                // Восстанавливаем оригинальную политику
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
    }
};
