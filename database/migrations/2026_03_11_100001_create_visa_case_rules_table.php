<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_case_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_code', 2)->index();
            $table->string('visa_type', 50);
            $table->string('visa_subtype', 50)->nullable();
            $table->string('applicant_type', 30)->default('adult');
            $table->string('embassy_platform', 50)->nullable();
            $table->string('submission_method', 30);
            $table->boolean('appointment_required')->default(true);
            $table->boolean('biometrics_required')->default(false);
            $table->boolean('personal_visit_required')->default(false);
            $table->boolean('interview_possible')->default(false);
            $table->unsignedSmallInteger('processing_days_min')->nullable();
            $table->unsignedSmallInteger('processing_days_max')->nullable();
            $table->decimal('consular_fee_eur', 8, 2)->nullable();
            $table->decimal('service_fee_eur', 8, 2)->nullable();
            $table->unsignedSmallInteger('max_stay_days')->nullable();
            $table->unsignedSmallInteger('validity_months')->nullable();
            $table->jsonb('workflow_steps');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['country_code', 'visa_type', 'visa_subtype', 'applicant_type'], 'vcr_country_visa_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_case_rules');
    }
};
