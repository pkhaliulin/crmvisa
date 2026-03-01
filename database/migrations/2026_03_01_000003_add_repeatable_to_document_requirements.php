<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requirements', function (Blueprint $table) {
            // Повторяемый документ — напр. "Метрика ребёнка" (добавляют +1 для каждого ребёнка)
            $table->boolean('is_repeatable')->default(false)->after('is_required');
        });

        Schema::table('case_checklist', function (Blueprint $table) {
            $table->boolean('is_repeatable')->default(false)->after('is_required');
            // Порядковый номер для повторяемых слотов (Ребёнок #1, #2...)
            $table->unsignedSmallInteger('repeat_index')->default(0)->after('is_repeatable');
        });
    }

    public function down(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->dropColumn(['is_repeatable', 'repeat_index']);
        });
        Schema::table('document_requirements', function (Blueprint $table) {
            $table->dropColumn('is_repeatable');
        });
    }
};
