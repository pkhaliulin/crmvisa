<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->string('risk_level', 10)->default('medium')->after('is_active');
            $table->smallInteger('destination_score_bonus')->default(0)->after('risk_level');
            // Новые веса для обновлённой модели скоринга
            $table->decimal('weight_finances', 4, 2)->default(0.25)->after('destination_score_bonus');
            $table->decimal('weight_visa_history', 4, 2)->default(0.30)->after('weight_finances');
            $table->decimal('weight_social_ties', 4, 2)->default(0.20)->after('weight_visa_history');
        });

        // Устанавливаем risk_level на основе страны
        DB::statement("UPDATE portal_countries SET risk_level = 'high' WHERE country_code IN ('US', 'CA', 'GB', 'AU')");
        DB::statement("UPDATE portal_countries SET risk_level = 'low' WHERE country_code IN ('TR', 'AE', 'TH', 'MY')");
        DB::statement("UPDATE portal_countries SET destination_score_bonus = -10 WHERE risk_level = 'high'");
        DB::statement("UPDATE portal_countries SET destination_score_bonus = 10 WHERE risk_level = 'low'");
    }

    public function down(): void
    {
        Schema::table('portal_countries', function (Blueprint $table) {
            $table->dropColumn(['risk_level', 'destination_score_bonus', 'weight_finances', 'weight_visa_history', 'weight_social_ties']);
        });
    }
};
