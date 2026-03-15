<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Добавить RLS-политики для case_refunds и case_financial_documents.
 * Аудит 15.03.2026 обнаружил что эти таблицы имеют agency_id, но без RLS.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ---- case_refunds ----
        DB::statement('ALTER TABLE case_refunds ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE case_refunds FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY tenant_isolation_case_refunds ON case_refunds
            USING (
                current_setting('app.is_superadmin', true) = 'true'
                OR agency_id::text = current_setting('app.current_tenant_id', true)
            )
            WITH CHECK (
                agency_id::text = current_setting('app.current_tenant_id', true)
            )
        ");

        // ---- case_financial_documents ----
        DB::statement('ALTER TABLE case_financial_documents ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE case_financial_documents FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY tenant_isolation_case_financial_documents ON case_financial_documents
            USING (
                current_setting('app.is_superadmin', true) = 'true'
                OR agency_id::text = current_setting('app.current_tenant_id', true)
            )
            WITH CHECK (
                agency_id::text = current_setting('app.current_tenant_id', true)
            )
        ");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS tenant_isolation_case_refunds ON case_refunds');
        DB::statement('ALTER TABLE case_refunds DISABLE ROW LEVEL SECURITY');

        DB::statement('DROP POLICY IF EXISTS tenant_isolation_case_financial_documents ON case_financial_documents');
        DB::statement('ALTER TABLE case_financial_documents DISABLE ROW LEVEL SECURITY');
    }
};
