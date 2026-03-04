<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            // Подача
            $table->jsonb('submission_types')->default('[]');
            $table->boolean('appointment_required')->default(false);
            $table->boolean('personal_submission_required')->default(false);
            $table->boolean('biometrics_required')->default(false);
            $table->boolean('photo_required')->default(false);
            $table->text('submission_notes')->nullable();

            // Визовый центр
            $table->boolean('has_visa_center')->default(false);

            // Посольство
            $table->boolean('has_embassy')->default(false);

            // Финансы
            $table->integer('min_balance_usd')->nullable();
            $table->string('min_balance_currency', 3)->default('USD');
            $table->string('min_income_currency', 3)->default('USD');
            $table->text('finance_notes')->nullable();
            $table->decimal('finance_threshold', 5, 2)->nullable();
            $table->text('finance_threshold_comment')->nullable();

            // Скоринг
            $table->text('scoring_empty_rule')->nullable();
        });

        Schema::table('country_visa_type_settings', function (Blueprint $table) {
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->dropColumn([
                'submission_types', 'appointment_required', 'personal_submission_required',
                'biometrics_required', 'photo_required', 'submission_notes',
                'has_visa_center', 'has_embassy',
                'min_balance_usd', 'min_balance_currency', 'min_income_currency',
                'finance_notes', 'finance_threshold', 'finance_threshold_comment',
                'scoring_empty_rule',
            ]);
        });

        Schema::table('country_visa_type_settings', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
