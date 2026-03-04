<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            // Посольство / визовый центр — флаги наличия
            if (! Schema::hasColumn('portal_countries', 'has_embassy')) {
                $table->boolean('has_embassy')->default(false)->after('longitude');
            }
            if (! Schema::hasColumn('portal_countries', 'has_visa_center')) {
                $table->boolean('has_visa_center')->default(false)->after('has_embassy');
            }

            // Подача — дополнительные поля
            if (! Schema::hasColumn('portal_countries', 'submission_types')) {
                $table->jsonb('submission_types')->nullable()->after('submission_type');
            }
            if (! Schema::hasColumn('portal_countries', 'appointment_required')) {
                $table->boolean('appointment_required')->default(false)->after('submission_types');
            }
            if (! Schema::hasColumn('portal_countries', 'personal_submission_required')) {
                $table->boolean('personal_submission_required')->default(false)->after('appointment_required');
            }
            if (! Schema::hasColumn('portal_countries', 'biometrics_required')) {
                $table->boolean('biometrics_required')->default(false)->after('personal_submission_required');
            }
            if (! Schema::hasColumn('portal_countries', 'photo_required')) {
                $table->boolean('photo_required')->default(false)->after('biometrics_required');
            }
            if (! Schema::hasColumn('portal_countries', 'submission_notes')) {
                $table->text('submission_notes')->nullable()->after('photo_required');
            }

            // Визовый центр — доп
            if (! Schema::hasColumn('portal_countries', 'visa_center_email')) {
                $table->string('visa_center_email', 255)->nullable()->after('visa_center_website');
            }
            if (! Schema::hasColumn('portal_countries', 'visa_center_notes')) {
                $table->text('visa_center_notes')->nullable()->after('visa_center_email');
            }

            // Финансы — доп
            if (! Schema::hasColumn('portal_countries', 'min_income_currency')) {
                $table->string('min_income_currency', 5)->default('USD')->after('min_monthly_income_usd');
            }
            if (! Schema::hasColumn('portal_countries', 'min_balance_usd')) {
                $table->unsignedInteger('min_balance_usd')->nullable()->after('min_income_currency');
            }
            if (! Schema::hasColumn('portal_countries', 'min_balance_currency')) {
                $table->string('min_balance_currency', 5)->default('USD')->after('min_balance_usd');
            }
            if (! Schema::hasColumn('portal_countries', 'finance_notes')) {
                $table->text('finance_notes')->nullable()->after('min_balance_currency');
            }
            if (! Schema::hasColumn('portal_countries', 'finance_threshold')) {
                $table->decimal('finance_threshold', 5, 2)->nullable()->after('finance_notes');
            }
            if (! Schema::hasColumn('portal_countries', 'finance_threshold_comment')) {
                $table->text('finance_threshold_comment')->nullable()->after('finance_threshold');
            }

            // Скоринг — доп
            if (! Schema::hasColumn('portal_countries', 'scoring_empty_rule')) {
                $table->text('scoring_empty_rule')->nullable()->after('destination_score_bonus');
            }
        });
    }

    public function down(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $cols = [
                'has_embassy', 'has_visa_center',
                'submission_types', 'appointment_required', 'personal_submission_required',
                'biometrics_required', 'photo_required', 'submission_notes',
                'visa_center_email', 'visa_center_notes',
                'min_income_currency', 'min_balance_usd', 'min_balance_currency',
                'finance_notes', 'finance_threshold', 'finance_threshold_comment',
                'scoring_empty_rule',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('portal_countries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
