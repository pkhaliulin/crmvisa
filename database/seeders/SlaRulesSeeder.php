<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlaRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // Шенген
            ['DE', 'tourist',  15, 30, 7],
            ['DE', 'business', 10, 20, 5],
            ['FR', 'tourist',  15, 30, 7],
            ['FR', 'business', 10, 20, 5],
            ['IT', 'tourist',  15, 30, 7],
            ['ES', 'tourist',  15, 30, 7],
            ['CZ', 'tourist',  10, 21, 5],
            ['PL', 'tourist',  10, 15, 5],
            // США
            ['US', 'tourist',  30, 90, 14],
            ['US', 'business', 30, 90, 14],
            // UK
            ['GB', 'tourist',  15, 21, 7],
            ['GB', 'business', 10, 15, 5],
            // ОАЭ
            ['AE', 'tourist',   3,  7, 2],
            ['AE', 'business',  3,  7, 2],
            // Турция
            ['TR', 'tourist',   1,  3, 1],
            // Корея
            ['KR', 'tourist',   5, 14, 5],
            // Китай
            ['CN', 'tourist',  10, 20, 5],
            ['CN', 'business',  7, 14, 3],
        ];

        $now = now();

        foreach ($rules as [$country, $type, $min, $max, $warning]) {
            DB::table('sla_rules')->upsert([
                'id'           => (string) Str::uuid(),
                'country_code' => $country,
                'visa_type'    => $type,
                'min_days'     => $min,
                'max_days'     => $max,
                'warning_days' => $warning,
                'is_active'    => true,
                'created_at'   => $now,
                'updated_at'   => $now,
            ], ['country_code', 'visa_type'], ['min_days', 'max_days', 'warning_days', 'updated_at']);
        }
    }
}
