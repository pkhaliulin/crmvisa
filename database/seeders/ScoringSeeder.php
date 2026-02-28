<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScoringSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Матрица весов по странам (сумма = 100 для каждой страны)
        // Блоки: F, E, FM, A, T, P, G
        $weights = [
            'DE' => ['F' => 25, 'E' => 20, 'FM' => 15, 'A' => 15, 'T' => 15, 'P' => 5, 'G' => 5],
            'FR' => ['F' => 25, 'E' => 20, 'FM' => 15, 'A' => 15, 'T' => 15, 'P' => 5, 'G' => 5],
            'US' => ['F' => 20, 'E' => 15, 'FM' => 25, 'A' => 20, 'T' => 10, 'P' => 5, 'G' => 5],
            'GB' => ['F' => 30, 'E' => 15, 'FM' => 15, 'A' => 15, 'T' => 15, 'P' => 5, 'G' => 5],
            'AE' => ['F' => 35, 'E' => 15, 'FM' => 10, 'A' => 15, 'T' => 10, 'P' => 10, 'G' => 5],
            'TR' => ['F' => 30, 'E' => 15, 'FM' => 10, 'A' => 15, 'T' => 15, 'P' => 10, 'G' => 5],
            'KR' => ['F' => 30, 'E' => 20, 'FM' => 10, 'A' => 15, 'T' => 15, 'P' => 5,  'G' => 5],
        ];

        foreach ($weights as $country => $blocks) {
            foreach ($blocks as $block => $weight) {
                DB::table('scoring_country_weights')->upsert([
                    'id'           => (string) Str::uuid(),
                    'country_code' => $country,
                    'block_code'   => $block,
                    'weight'       => $weight,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ], ['country_code', 'block_code'], ['weight', 'updated_at']);
            }
        }

        // Финансовые пороги
        $thresholds = [
            ['DE', 3000,  800, 90,  'Schengen — стандарт'],
            ['FR', 3000,  800, 90,  'Schengen — стандарт'],
            ['US', 6000, 1500, 180, 'B1/B2 — строгий контроль'],
            ['GB', 4000, 1000, 180, 'UK — нет строгого минимума'],
            ['AE', 2000,  600, 30,  'ОАЭ — лояльные требования'],
            ['TR', 1500,  500, 30,  'Турция — самые мягкие'],
            ['KR', 2000,  800, 90,  'Корея — стандарт'],
        ];

        foreach ($thresholds as [$country, $balance, $income, $days, $notes]) {
            DB::table('scoring_financial_thresholds')->upsert([
                'id'                 => (string) Str::uuid(),
                'country_code'       => $country,
                'min_bank_balance'   => $balance,
                'min_monthly_income' => $income,
                'bank_history_days'  => $days,
                'notes'              => $notes,
                'created_at'         => $now,
                'updated_at'         => $now,
            ], ['country_code'], ['min_bank_balance', 'min_monthly_income', 'bank_history_days', 'notes', 'updated_at']);
        }
    }
}
