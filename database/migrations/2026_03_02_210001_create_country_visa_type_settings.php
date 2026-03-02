<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('country_visa_type_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('country_code', 2);
            $table->string('visa_type', 50);

            // Timeline
            $table->smallInteger('preparation_days')->default(5);
            $table->smallInteger('appointment_wait_days')->default(10);
            $table->smallInteger('processing_days_min')->default(5);
            $table->smallInteger('processing_days_max')->default(30);
            $table->smallInteger('processing_days_avg')->default(15);
            $table->smallInteger('buffer_days')->default(7);
            $table->boolean('parallel_docs_allowed')->default(true);

            // Calculated (auto-filled on save)
            $table->smallInteger('min_days_before_departure')->default(30);
            $table->smallInteger('recommended_days_before_departure')->default(45);

            // Parameters
            $table->decimal('avg_refusal_rate', 5, 2)->nullable();
            $table->boolean('biometrics_required')->default(false);
            $table->boolean('personal_visit_required')->default(true);
            $table->boolean('interview_required')->default(false);

            // Appointment
            $table->string('appointment_pattern', 30)->default('daily_slots');
            $table->text('appointment_notes')->nullable();

            // Cost
            $table->decimal('consular_fee_usd', 8, 2)->nullable();
            $table->decimal('service_fee_usd', 8, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['country_code', 'visa_type']);
            $table->index('country_code');
            $table->index('visa_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_visa_type_settings');
    }
};
