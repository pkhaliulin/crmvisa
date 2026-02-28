<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BillingPlansSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('billing_plans')->upsert([
            [
                'slug'                    => 'trial',
                'name'                    => 'Trial',
                'price_monthly'           => 0,
                'price_yearly'            => 0,
                'price_uzs'               => 0,
                'max_managers'            => 3,
                'max_cases'               => 50,
                'has_marketplace'         => false,
                'has_priority_support'    => false,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
                'features'                => json_encode([
                    '30 дней бесплатно',
                    'До 3 менеджеров',
                    'До 50 активных заявок',
                    'SLA-дедлайны',
                    'Канбан-доска',
                ]),
                'sort_order'              => 0,
                'is_active'               => true,
                'created_at'              => now(),
                'updated_at'              => now(),
            ],
            [
                'slug'                    => 'starter',
                'name'                    => 'Starter',
                'price_monthly'           => 1900,     // $19
                'price_yearly'            => 18240,    // $182.40 (~20% скидка)
                'price_uzs'               => 240000,   // ~240 000 сум
                'max_managers'            => 3,
                'max_cases'               => 50,
                'has_marketplace'         => false,
                'has_priority_support'    => false,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
                'features'                => json_encode([
                    'До 3 менеджеров',
                    'До 50 активных заявок',
                    'SLA-дедлайны + уведомления',
                    'Канбан-доска',
                    'Telegram-уведомления клиентам',
                    'Скоринг-движок',
                ]),
                'sort_order'              => 1,
                'is_active'               => true,
                'created_at'              => now(),
                'updated_at'              => now(),
            ],
            [
                'slug'                    => 'pro',
                'name'                    => 'Professional',
                'price_monthly'           => 4900,     // $49
                'price_yearly'            => 47040,    // $470.40
                'price_uzs'               => 620000,
                'max_managers'            => 10,
                'max_cases'               => 200,
                'has_marketplace'         => true,
                'has_priority_support'    => false,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
                'features'                => json_encode([
                    'До 10 менеджеров',
                    'До 200 активных заявок',
                    'Листинг на маркетплейсе CRMBOR',
                    'Входящие лиды с маркетплейса',
                    'White-label Telegram-бот',
                    'Приоритетная поддержка',
                ]),
                'sort_order'              => 2,
                'is_active'               => true,
                'created_at'              => now(),
                'updated_at'              => now(),
            ],
            [
                'slug'                    => 'enterprise',
                'name'                    => 'Enterprise',
                'price_monthly'           => 9900,     // $99
                'price_yearly'            => 95040,    // $950.40
                'price_uzs'               => 1250000,
                'max_managers'            => 0,        // unlimited
                'max_cases'               => 0,        // unlimited
                'has_marketplace'         => true,
                'has_priority_support'    => true,
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly'  => null,
                'features'                => json_encode([
                    'Безлимитные менеджеры',
                    'Безлимитные заявки',
                    'Приоритетный значок на маркетплейсе',
                    'Персональный менеджер CRMBOR',
                    'Custom домен для клиентского портала',
                    'API-доступ',
                    'SLA-гарантия 99.9%',
                ]),
                'sort_order'              => 3,
                'is_active'               => true,
                'created_at'              => now(),
                'updated_at'              => now(),
            ],
        ], ['slug'], [
            'name', 'price_monthly', 'price_yearly', 'price_uzs',
            'max_managers', 'max_cases', 'has_marketplace',
            'features', 'sort_order', 'updated_at',
        ]);
    }
}
