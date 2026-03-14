<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') return;

        Schema::table('cases', function (Blueprint $table) {
            $table->index(['assigned_to', 'stage'], 'idx_cases_assigned_stage');
        });
        Schema::table('public_leads', function (Blueprint $table) {
            $table->index(['assigned_agency_id', 'status'], 'idx_public_leads_agency_status');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') return;

        Schema::table('cases', function (Blueprint $table) {
            $table->dropIndex('idx_cases_assigned_stage');
        });
        Schema::table('public_leads', function (Blueprint $table) {
            $table->dropIndex('idx_public_leads_agency_status');
        });
    }
};
