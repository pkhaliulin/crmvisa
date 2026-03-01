<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            // Новая ссылка на country_visa_requirements (заменяет старый requirement_id → document_requirements)
            $table->foreignUuid('country_requirement_id')
                  ->nullable()
                  ->after('requirement_id')
                  ->constrained('country_visa_requirements')
                  ->nullOnDelete();

            // Уровень требования копируется из country_visa_requirements при создании чек-листа
            $table->enum('requirement_level', ['required', 'recommended', 'confirmation_only'])
                  ->default('required')
                  ->after('country_requirement_id');

            // Metadata из шаблона (минимальный баланс, период выписки и т.д.)
            $table->jsonb('metadata')->nullable()->after('requirement_level');
        });
    }

    public function down(): void
    {
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->dropForeign(['country_requirement_id']);
            $table->dropColumn(['country_requirement_id', 'requirement_level', 'metadata']);
        });
    }
};
