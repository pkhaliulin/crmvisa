<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Добавить stage_sla_days в sla_rules
        Schema::table('sla_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('sla_rules', 'stage_sla_days')) {
                // {"lead":2,"qualification":3,"documents":7,"translation":5,"appointment":3,"review":30}
                $table->jsonb('stage_sla_days')->default('{}');
            }
        });

        // Добавить sla_due_at и is_overdue в case_stages
        Schema::table('case_stages', function (Blueprint $table) {
            if (! Schema::hasColumn('case_stages', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable();
            }
            if (! Schema::hasColumn('case_stages', 'is_overdue')) {
                $table->boolean('is_overdue')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sla_rules', function (Blueprint $table) {
            $table->dropColumn('stage_sla_days');
        });

        Schema::table('case_stages', function (Blueprint $table) {
            $table->dropColumn(['sla_due_at', 'is_overdue']);
        });
    }
};
