<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // Расширяем enum provider: добавляем internal, click, uzum
            DB::statement("ALTER TABLE payment_transactions DROP CONSTRAINT IF EXISTS payment_transactions_provider_check");
            DB::statement("ALTER TABLE payment_transactions ADD CONSTRAINT payment_transactions_provider_check CHECK (provider IN ('stripe', 'payme', 'manual', 'internal', 'click', 'uzum'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payment_transactions DROP CONSTRAINT IF EXISTS payment_transactions_provider_check");
            DB::statement("ALTER TABLE payment_transactions ADD CONSTRAINT payment_transactions_provider_check CHECK (provider IN ('stripe', 'payme', 'manual'))");
        }
    }
};
