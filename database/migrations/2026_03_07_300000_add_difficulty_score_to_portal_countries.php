<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('portal_countries', 'difficulty_score')) {
            Schema::table('portal_countries', function (Blueprint $table) {
                $table->smallInteger('difficulty_score')->default(50)->after('destination_score_bonus');
            });
        }

        // Рассчитываем difficulty_score из существующих данных
        $countries = DB::table('portal_countries')->get();

        foreach ($countries as $c) {
            $d = match ($c->visa_regime) {
                'visa_free'       => 5,
                'visa_on_arrival' => 12,
                'evisa'           => 22,
                default           => 40, // visa_required
            };

            // Финансовые требования
            $income = (int) ($c->min_monthly_income_usd ?? 0);
            $d += min(20, $income / 125);

            $balance = (int) ($c->min_balance_usd ?? 0);
            $d += min(15, $balance / 533);

            // Уровень риска
            $d += match ($c->risk_level ?? 'medium') {
                'high' => 15,
                'low'  => -5,
                default => 0,
            };

            // Высокий процент отказов
            if ($c->is_high_refusal ?? false) {
                $d += 8;
            }

            // destination_score_bonus как ручная поправка (инвертированная)
            $d -= (int) ($c->destination_score_bonus ?? 0);

            $d = max(5, min(95, (int) round($d)));

            DB::table('portal_countries')
                ->where('country_code', $c->country_code)
                ->update(['difficulty_score' => $d]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('portal_countries', 'difficulty_score')) {
            Schema::table('portal_countries', function (Blueprint $table) {
                $table->dropColumn('difficulty_score');
            });
        }
    }
};
