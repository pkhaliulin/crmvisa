<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->unique()->constrained()->cascadeOnDelete();

            // Block F — Финансы
            $table->unsignedInteger('monthly_income')->default(0);       // USD
            $table->enum('income_type', ['official', 'informal', 'business', 'mixed'])->default('official');
            $table->unsignedInteger('bank_balance')->default(0);         // USD
            $table->unsignedSmallInteger('bank_history_months')->default(0);
            $table->boolean('bank_balance_stable')->default(false);
            $table->boolean('has_fixed_deposit')->default(false);
            $table->unsignedInteger('fixed_deposit_amount')->default(0);
            $table->boolean('has_investments')->default(false);
            $table->unsignedInteger('investments_amount')->default(0);

            // Block E — Занятость
            $table->enum('employment_type', ['government', 'private', 'business_owner', 'self_employed', 'retired', 'student', 'unemployed'])->default('private');
            $table->string('employer_name')->nullable();
            $table->string('position')->nullable();
            $table->enum('position_level', ['executive', 'senior', 'mid', 'junior', 'intern'])->nullable();
            $table->decimal('years_at_current_job', 4, 1)->default(0);
            $table->decimal('total_work_experience', 4, 1)->default(0);
            $table->boolean('has_employment_gaps')->default(false);

            // Block FM — Семья
            $table->enum('marital_status', ['married', 'single', 'divorced', 'widowed'])->default('single');
            $table->boolean('spouse_employed')->default(false);
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->boolean('children_staying_home')->default(false);
            $table->unsignedTinyInteger('dependents_count')->default(0);

            // Block A — Активы
            $table->boolean('has_real_estate')->default(false);
            $table->boolean('has_car')->default(false);
            $table->boolean('has_business')->default(false);

            // Block T — История виз
            $table->boolean('has_schengen_visa')->default(false);
            $table->boolean('has_us_visa')->default(false);
            $table->boolean('has_uk_visa')->default(false);
            $table->unsignedTinyInteger('previous_refusals')->default(0);
            $table->boolean('has_overstay')->default(false);

            // Block P — Личные
            $table->enum('education_level', ['none', 'secondary', 'bachelor', 'master', 'phd'])->default('secondary');
            $table->boolean('has_criminal_record')->default(false);
            $table->unsignedTinyInteger('age')->nullable();

            // Block G — Цель поездки
            $table->enum('travel_purpose', ['tourism', 'business', 'education', 'treatment', 'family_visit'])->default('tourism');
            $table->boolean('has_return_ticket')->default(false);
            $table->boolean('has_hotel_booking')->default(false);
            $table->boolean('has_invitation_letter')->default(false);
            $table->unsignedSmallInteger('trip_duration_days')->default(0);
            $table->boolean('sponsor_covers_expenses')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};
