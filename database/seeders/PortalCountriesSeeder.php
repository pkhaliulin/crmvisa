<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PortalCountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['country_code' => 'DE', 'name' => 'Германия',        'flag_emoji' => '🇩🇪', 'weight_finance' => 0.40, 'weight_ties' => 0.35, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 1000, 'min_score' => 35, 'sort_order' => 1],
            ['country_code' => 'ES', 'name' => 'Испания',          'flag_emoji' => '🇪🇸', 'weight_finance' => 0.35, 'weight_ties' => 0.40, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 800,  'min_score' => 30, 'sort_order' => 2],
            ['country_code' => 'FR', 'name' => 'Франция',          'flag_emoji' => '🇫🇷', 'weight_finance' => 0.40, 'weight_ties' => 0.35, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 1000, 'min_score' => 35, 'sort_order' => 3],
            ['country_code' => 'IT', 'name' => 'Италия',           'flag_emoji' => '🇮🇹', 'weight_finance' => 0.35, 'weight_ties' => 0.40, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 800,  'min_score' => 30, 'sort_order' => 4],
            ['country_code' => 'PL', 'name' => 'Польша',           'flag_emoji' => '🇵🇱', 'weight_finance' => 0.35, 'weight_ties' => 0.40, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 600,  'min_score' => 25, 'sort_order' => 5],
            ['country_code' => 'CZ', 'name' => 'Чехия',            'flag_emoji' => '🇨🇿', 'weight_finance' => 0.35, 'weight_ties' => 0.40, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 600,  'min_score' => 25, 'sort_order' => 6],
            ['country_code' => 'GB', 'name' => 'Великобритания',   'flag_emoji' => '🇬🇧', 'weight_finance' => 0.35, 'weight_ties' => 0.40, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 1200, 'min_score' => 40, 'sort_order' => 7],
            ['country_code' => 'US', 'name' => 'США',              'flag_emoji' => '🇺🇸', 'weight_finance' => 0.25, 'weight_ties' => 0.50, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 1500, 'min_score' => 45, 'sort_order' => 8],
            ['country_code' => 'CA', 'name' => 'Канада',           'flag_emoji' => '🇨🇦', 'weight_finance' => 0.25, 'weight_ties' => 0.50, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 1500, 'min_score' => 45, 'sort_order' => 9],
            ['country_code' => 'KR', 'name' => 'Южная Корея',      'flag_emoji' => '🇰🇷', 'weight_finance' => 0.30, 'weight_ties' => 0.45, 'weight_travel' => 0.15, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 800,  'min_score' => 30, 'sort_order' => 10],
            ['country_code' => 'AE', 'name' => 'ОАЭ',              'flag_emoji' => '🇦🇪', 'weight_finance' => 0.35, 'weight_ties' => 0.35, 'weight_travel' => 0.20, 'weight_profile' => 0.10, 'min_monthly_income_usd' => 500,  'min_score' => 20, 'sort_order' => 11],
        ];

        foreach ($countries as $c) {
            DB::table('portal_countries')->upsert(
                array_merge($c, ['created_at' => now(), 'updated_at' => now()]),
                ['country_code'],
                ['name', 'flag_emoji', 'weight_finance', 'weight_ties', 'weight_travel', 'weight_profile',
                 'min_monthly_income_usd', 'min_score', 'sort_order', 'updated_at']
            );
        }
    }
}
