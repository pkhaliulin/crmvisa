<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            [
                'key'         => 'marketplace',
                'name'        => 'Marketplace',
                'description' => 'Доступ к маркетплейсу агентств',
                'enabled'     => true,
                'plans'       => ['pro', 'enterprise'],
            ],
            [
                'key'         => 'scoring_v2',
                'name'        => 'Scoring V2',
                'description' => 'Расширенная модель скоринга с ML',
                'enabled'     => false,
                'plans'       => ['enterprise'],
            ],
            [
                'key'         => 'telegram_bot',
                'name'        => 'Telegram Bot',
                'description' => 'Telegram бот для агентства',
                'enabled'     => true,
                'plans'       => ['starter', 'pro', 'enterprise'],
            ],
            [
                'key'         => 'priority_support',
                'name'        => 'Priority Support',
                'description' => 'Приоритетная поддержка',
                'enabled'     => true,
                'plans'       => ['pro', 'enterprise'],
            ],
            [
                'key'         => 'advanced_reports',
                'name'        => 'Advanced Reports',
                'description' => 'Расширенная аналитика и отчёты',
                'enabled'     => true,
                'plans'       => ['pro', 'enterprise'],
            ],
            [
                'key'         => 'api_access',
                'name'        => 'API Access',
                'description' => 'Доступ к внешнему API',
                'enabled'     => false,
                'plans'       => ['enterprise'],
            ],
        ];

        foreach ($flags as $flag) {
            DB::table('feature_flags')->updateOrInsert(
                ['key' => $flag['key']],
                [
                    'id'              => Str::uuid(),
                    'name'            => $flag['name'],
                    'description'     => $flag['description'],
                    'enabled'         => $flag['enabled'],
                    'rollout_percent' => 100,
                    'plans'           => json_encode($flag['plans']),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
        }
    }
}
