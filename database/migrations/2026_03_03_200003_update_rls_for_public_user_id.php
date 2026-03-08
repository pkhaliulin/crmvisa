<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // 1. clients — доступ по public_user_id (прямая колонка)
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_clients ON clients");
            DB::statement("
                CREATE POLICY tenant_isolation_clients ON clients
                FOR ALL
                USING (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    OR public_user_id::text = current_setting('app.public_user_id', true)
                )
                WITH CHECK (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    OR public_user_id::text = current_setting('app.public_user_id', true)
                )
            ");

            // 2. cases — доступ через подзапрос к clients.public_user_id
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_cases ON cases");
            DB::statement("
                CREATE POLICY tenant_isolation_cases ON cases
                FOR ALL
                USING (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    OR EXISTS (
                        SELECT 1 FROM clients
                        WHERE clients.id = cases.client_id
                        AND clients.public_user_id::text = current_setting('app.public_user_id', true)
                    )
                )
                WITH CHECK (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    OR EXISTS (
                        SELECT 1 FROM clients
                        WHERE clients.id = cases.client_id
                        AND clients.public_user_id::text = current_setting('app.public_user_id', true)
                    )
                )
            ");

            // 3. case_checklist — доступ через cases -> clients.public_user_id
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_case_checklist ON case_checklist");
            DB::statement("
                CREATE POLICY tenant_isolation_case_checklist ON case_checklist
                FOR ALL
                USING (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    OR EXISTS (
                        SELECT 1 FROM cases
                        JOIN clients ON clients.id = cases.client_id
                        WHERE cases.id = case_checklist.case_id
                        AND clients.public_user_id::text = current_setting('app.public_user_id', true)
                    )
                )
                WITH CHECK (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    OR EXISTS (
                        SELECT 1 FROM cases
                        JOIN clients ON clients.id = cases.client_id
                        WHERE cases.id = case_checklist.case_id
                        AND clients.public_user_id::text = current_setting('app.public_user_id', true)
                    )
                )
            ");

            // 4. documents — доступ через client_id -> clients.public_user_id
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_documents ON documents");
            DB::statement("
                CREATE POLICY tenant_isolation_documents ON documents
                FOR ALL
                USING (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    OR EXISTS (
                        SELECT 1 FROM clients
                        WHERE clients.id = documents.client_id
                        AND clients.public_user_id::text = current_setting('app.public_user_id', true)
                    )
                )
                WITH CHECK (
                    agency_id::text = current_setting('app.current_tenant_id', true)
                    OR current_setting('app.is_superadmin', true) = 'true'
                    OR (agency_id IS NULL AND current_setting('app.is_public_user', true) = 'true')
                    OR EXISTS (
                        SELECT 1 FROM clients
                        WHERE clients.id = documents.client_id
                        AND clients.public_user_id::text = current_setting('app.public_user_id', true)
                    )
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // Откат к предыдущим политикам (без public_user_id)
            $tablesSimple = ['clients', 'documents', 'case_checklist'];
            foreach ($tablesSimple as $table) {
                DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table}");
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

            DB::statement("DROP POLICY IF EXISTS tenant_isolation_cases ON cases");
            DB::statement("
                CREATE POLICY tenant_isolation_cases ON cases
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
};
