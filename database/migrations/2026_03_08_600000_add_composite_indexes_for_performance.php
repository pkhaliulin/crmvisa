<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // cases: основной запрос дашборда и канбана
        Schema::table('cases', function (Blueprint $table) {
            $table->index(['agency_id', 'stage', 'public_status'], 'idx_cases_agency_stage_status');
            $table->index(['agency_id', 'stage', 'created_at'], 'idx_cases_agency_stage_created');
            $table->index(['agency_id', 'critical_date', 'stage'], 'idx_cases_agency_critical_stage');
            $table->index(['agency_id', 'assigned_to', 'stage'], 'idx_cases_agency_assigned_stage');
        });

        // case_stages: SLA аналитика
        Schema::table('case_stages', function (Blueprint $table) {
            $table->index(['case_id', 'stage', 'entered_at'], 'idx_case_stages_case_stage_entered');
            $table->index(['sla_due_at', 'is_overdue', 'exited_at'], 'idx_case_stages_sla_overdue');
        });

        // client_payments: финансовая сводка
        Schema::table('client_payments', function (Blueprint $table) {
            $table->index(['agency_id', 'status', 'created_at'], 'idx_payments_agency_status_created');
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('cases', function (Blueprint $table) {
            $table->dropIndex('idx_cases_agency_stage_status');
            $table->dropIndex('idx_cases_agency_stage_created');
            $table->dropIndex('idx_cases_agency_critical_stage');
            $table->dropIndex('idx_cases_agency_assigned_stage');
        });

        Schema::table('case_stages', function (Blueprint $table) {
            $table->dropIndex('idx_case_stages_case_stage_entered');
            $table->dropIndex('idx_case_stages_sla_overdue');
        });

        Schema::table('client_payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_agency_status_created');
        });
    }
};
