<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_source_check");
            DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_source_check CHECK (source IN ('direct', 'referral', 'marketplace', 'other', 'group_invite'))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_source_check");
            DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_source_check CHECK (source IN ('direct', 'referral', 'marketplace', 'other'))");
        }
    }
};
