<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Публичный портал создаёт записи с agency_id = NULL (draft заявки)
        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('agency_id')->nullable()->change();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('agency_id')->nullable()->change();
        });

        Schema::table('case_checklist', function (Blueprint $table) {
            $table->uuid('agency_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Осторожно: rollback невозможен если есть NULL записи
        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('agency_id')->nullable(false)->change();
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->uuid('agency_id')->nullable(false)->change();
        });

        Schema::table('case_checklist', function (Blueprint $table) {
            $table->uuid('agency_id')->nullable(false)->change();
        });
    }
};
