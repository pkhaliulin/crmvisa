<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('country_visa_requirements', function (Blueprint $table) {
            $table->string('target_audience', 30)->default('applicant')->after('display_order');
            // applicant        — только для основного заявителя
            // family_all       — для всех членов семьи
            // family_spouse    — только для супруга/супруги
            // family_child     — для детей (любой возраст)
            // family_minor     — только для несовершеннолетних
            // family_parent    — только для родителей
            // both             — и для заявителя, и для семьи

            $table->index('target_audience');
        });

        // Обновляем unique constraint — теперь включает target_audience
        Schema::table('country_visa_requirements', function (Blueprint $table) {
            $table->dropUnique('cvr_unique');
            $table->unique(['country_code', 'visa_type', 'document_template_id', 'target_audience'], 'cvr_unique');
        });
    }

    public function down(): void
    {
        Schema::table('country_visa_requirements', function (Blueprint $table) {
            $table->dropUnique('cvr_unique');
            $table->unique(['country_code', 'visa_type', 'document_template_id'], 'cvr_unique');
        });

        Schema::table('country_visa_requirements', function (Blueprint $table) {
            $table->dropIndex(['target_audience']);
            $table->dropColumn('target_audience');
        });
    }
};
