<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite: пересоздаём таблицу с расширенным CHECK constraint
            DB::statement("PRAGMA writable_schema = ON");
            $row = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='payment_transactions'");
            if ($row) {
                $newSql = str_replace(
                    "in ('stripe', 'payme', 'manual')",
                    "in ('stripe', 'payme', 'manual', 'internal', 'click', 'uzum')",
                    $row->sql
                );
                if ($newSql === $row->sql) {
                    // Попробуем вариант с кавычками
                    $newSql = str_replace(
                        "in (\"stripe\", \"payme\", \"manual\")",
                        "in (\"stripe\", \"payme\", \"manual\", \"internal\", \"click\", \"uzum\")",
                        $row->sql
                    );
                }
                if ($newSql !== $row->sql) {
                    DB::statement("UPDATE sqlite_master SET sql = ? WHERE type='table' AND name='payment_transactions'", [$newSql]);
                }
            }
            DB::statement("PRAGMA writable_schema = OFF");
        } else {
            // PostgreSQL: расширяем enum provider
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
