<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DROP POLICY IF EXISTS tenant_isolation_users ON users");

        DB::statement("
            CREATE POLICY tenant_isolation_users ON users
            FOR ALL
            USING (
                agency_id::text = current_setting('app.current_tenant_id', true)
                OR current_setting('app.is_superadmin', true) = 'true'
                OR (
                    current_setting('app.is_public_user', true) = 'true'
                    AND EXISTS (
                        SELECT 1 FROM cases
                        WHERE cases.assigned_to = users.id
                        AND EXISTS (
                            SELECT 1 FROM clients
                            WHERE clients.id = cases.client_id
                            AND clients.public_user_id::text = current_setting('app.public_user_id', true)
                        )
                    )
                )
            )
            WITH CHECK (
                agency_id::text = current_setting('app.current_tenant_id', true)
                OR current_setting('app.is_superadmin', true) = 'true'
            )
        ");
    }

    public function down(): void
    {
        DB::statement("DROP POLICY IF EXISTS tenant_isolation_users ON users");

        DB::statement("
            CREATE POLICY tenant_isolation_users ON users
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
};
