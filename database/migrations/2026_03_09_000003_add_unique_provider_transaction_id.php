<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Partial unique index — только для ненулевых provider_transaction_id
            DB::statement('
                CREATE UNIQUE INDEX IF NOT EXISTS client_payments_provider_txn_unique
                ON client_payments (provider, provider_transaction_id)
                WHERE provider_transaction_id IS NOT NULL
            ');
        } else {
            // SQLite/MySQL — обычный unique index (NULL не учитываются в unique для SQLite)
            Schema::table('client_payments', function ($table) {
                $table->unique(['provider', 'provider_transaction_id'], 'client_payments_provider_txn_unique');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS client_payments_provider_txn_unique');
        } else {
            Schema::table('client_payments', function ($table) {
                $table->dropUnique('client_payments_provider_txn_unique');
            });
        }
    }
};
