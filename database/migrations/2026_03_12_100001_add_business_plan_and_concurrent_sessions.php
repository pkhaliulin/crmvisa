<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Расширить enum plan для agencies (PostgreSQL → убрать constraint, SQLite → уже varchar)
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE agencies DROP CONSTRAINT IF EXISTS agencies_plan_check");
            DB::statement("ALTER TABLE agencies ALTER COLUMN plan TYPE varchar(20)");
        }

        // 2. Добавить max_concurrent_sessions в billing_plans
        if (Schema::hasTable('billing_plans') && ! Schema::hasColumn('billing_plans', 'max_concurrent_sessions')) {
            Schema::table('billing_plans', function (Blueprint $table) {
                $table->unsignedSmallInteger('max_concurrent_sessions')
                      ->default(0)
                      ->after('max_leads_per_month')
                      ->comment('0 = unlimited');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('billing_plans') && Schema::hasColumn('billing_plans', 'max_concurrent_sessions')) {
            Schema::table('billing_plans', function (Blueprint $table) {
                $table->dropColumn('max_concurrent_sessions');
            });
        }
    }
};
