<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->boolean('ai_enabled')->default(true)->after('id');
            $table->jsonb('ai_extraction_schema')->nullable()->after('ai_enabled');
            $table->jsonb('ai_validation_rules')->nullable()->after('ai_extraction_schema');
            $table->jsonb('ai_stop_factors')->nullable()->after('ai_validation_rules');
            $table->jsonb('ai_success_factors')->nullable()->after('ai_stop_factors');
            $table->jsonb('ai_risk_indicators')->nullable()->after('ai_success_factors');
            $table->text('manager_instructions')->nullable()->after('ai_risk_indicators');
            $table->boolean('translation_required')->default(false)->after('manager_instructions');
            $table->integer('max_age_days')->nullable()->after('translation_required');
            $table->jsonb('confidence_criteria')->nullable()->after('max_age_days');
        });

        Schema::table('country_visa_requirements', function (Blueprint $table) {
            $table->text('ai_country_notes')->nullable()->after('id');
            $table->jsonb('ai_override_extraction')->nullable()->after('ai_country_notes');
            $table->decimal('min_amount_local', 12, 2)->nullable()->after('ai_override_extraction');
            $table->string('min_amount_currency', 3)->nullable()->after('min_amount_local');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn([
                'ai_enabled',
                'ai_extraction_schema',
                'ai_validation_rules',
                'ai_stop_factors',
                'ai_success_factors',
                'ai_risk_indicators',
                'manager_instructions',
                'translation_required',
                'max_age_days',
                'confidence_criteria',
            ]);
        });

        Schema::table('country_visa_requirements', function (Blueprint $table) {
            $table->dropColumn([
                'ai_country_notes',
                'ai_override_extraction',
                'min_amount_local',
                'min_amount_currency',
            ]);
        });
    }
};
