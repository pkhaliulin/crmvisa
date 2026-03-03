<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Таблицы для nullable agency_id.
     * clients нужно только DROP NOT NULL (политика уже обновлена в 200000).
     * documents и case_checklist — DROP NOT NULL + обновить RLS-политику.
     */
    public function up(): void
    {
        // 1. clients — только nullable (RLS уже обновлена миграцией 200000)
        DB::statement("DROP POLICY IF EXISTS tenant_isolation_clients ON clients");
        DB::statement("ALTER TABLE clients ALTER COLUMN agency_id DROP NOT NULL");
        DB::statement("
            CREATE POLICY tenant_isolation_clients ON clients
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

        // 2. documents — nullable + обновить RLS
        DB::statement("DROP POLICY IF EXISTS tenant_isolation_documents ON documents");
        DB::statement("ALTER TABLE documents ALTER COLUMN agency_id DROP NOT NULL");
        DB::statement("
            CREATE POLICY tenant_isolation_documents ON documents
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

        // 3. case_checklist — nullable + обновить RLS
        DB::statement("DROP POLICY IF EXISTS tenant_isolation_case_checklist ON case_checklist");
        DB::statement("ALTER TABLE case_checklist ALTER COLUMN agency_id DROP NOT NULL");
        DB::statement("
            CREATE POLICY tenant_isolation_case_checklist ON case_checklist
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

    public function down(): void
    {
        $tables = ['clients', 'documents', 'case_checklist'];

        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table}");

            // Восстанавливаем NOT NULL (может упасть если есть NULL записи)
            DB::statement("ALTER TABLE {$table} ALTER COLUMN agency_id SET NOT NULL");

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
