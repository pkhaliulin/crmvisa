<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Таблицы с мультитенантностью (agency_id).
     * RLS обеспечивает изоляцию данных на уровне PostgreSQL.
     */
    private array $tenantTables = [
        'clients',
        'cases',
        'documents',
        'users',
        'agency_subscriptions',
        'payment_transactions',
        'marketplace_leads',
        'agency_profiles',
        'agency_service_packages',
        'agency_work_countries',
        'agency_reviews',
        'case_checklist',
    ];

    public function up(): void
    {
        foreach ($this->tenantTables as $table) {
            // Включаем RLS на таблице
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");

            // FORCE RLS — действует даже для владельца таблицы (но не для superuser)
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");

            // Политика изоляции тенанта
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

    public function down(): void
    {
        foreach ($this->tenantTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation_{$table} ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
