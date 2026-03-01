<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ----------------------------------------------------------------
        // Публичные пользователи (лендинг, не CRM)
        // Авторизуются по номеру телефона + PIN
        // ----------------------------------------------------------------
        Schema::create('public_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone', 20)->unique();
            $table->string('pin_hash')->nullable();          // bcrypt, null до первого входа
            $table->string('api_token', 80)->nullable();     // sha256 plain token

            // Профиль (заполняется вручную или через OCR)
            $table->string('name')->nullable();
            $table->date('dob')->nullable();
            $table->string('citizenship', 2)->nullable();    // ISO Alpha-2
            $table->string('gender', 1)->nullable();         // M / F

            // Паспортные данные (из OCR)
            $table->string('passport_number', 50)->nullable();
            $table->date('passport_expires_at')->nullable();
            $table->enum('ocr_status', ['pending', 'processing', 'done', 'failed'])->nullable();
            $table->jsonb('ocr_raw_data')->nullable();

            // Занятость и финансы (для скоринга)
            $table->enum('employment_type', [
                'employed', 'self_employed', 'business_owner',
                'student', 'retired', 'unemployed',
            ])->nullable();
            $table->unsignedInteger('monthly_income_usd')->nullable();

            // Привязанность к родине (для скоринга)
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->boolean('has_children')->default(false);
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->boolean('has_property')->default(false);
            $table->boolean('has_car')->default(false);

            // История поездок (для скоринга)
            $table->boolean('has_schengen_visa')->default(false);
            $table->boolean('has_us_visa')->default(false);
            $table->boolean('had_visa_refusal')->default(false);
            $table->boolean('had_overstay')->default(false);

            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index('api_token');
        });

        // ----------------------------------------------------------------
        // OTP-коды для подтверждения телефона
        // ----------------------------------------------------------------
        Schema::create('public_otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone', 20)->index();
            $table->string('code', 6);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        // ----------------------------------------------------------------
        // Кэш результатов скоринга по странам
        // ----------------------------------------------------------------
        Schema::create('public_score_cache', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('public_user_id')->constrained('public_users')->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->unsignedSmallInteger('score');          // 0–100
            $table->jsonb('breakdown')->nullable();          // {finance, ties, travel, profile}
            $table->jsonb('recommendations')->nullable();    // array of strings
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['public_user_id', 'country_code']);
        });

        // ----------------------------------------------------------------
        // Лиды — пользователи, прошедшие скоринг (попадают в CRM)
        // ----------------------------------------------------------------
        Schema::create('public_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('public_user_id')->constrained('public_users')->cascadeOnDelete();
            $table->string('country_code', 2);
            $table->string('visa_type', 50)->default('tourist');
            $table->unsignedSmallInteger('score')->default(0);
            $table->enum('status', ['new', 'contacted', 'assigned', 'converted'])->default('new');
            $table->foreignUuid('assigned_agency_id')
                  ->nullable()
                  ->constrained('agencies')
                  ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['country_code', 'status']);
            $table->index(['assigned_agency_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_leads');
        Schema::dropIfExists('public_score_cache');
        Schema::dropIfExists('public_otp_codes');
        Schema::dropIfExists('public_users');
    }
};
