<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PortalReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedVisaTypes();
        $this->seedCountries();
    }

    // -------------------------------------------------------------------------
    // Типы виз
    // -------------------------------------------------------------------------

    private function seedVisaTypes(): void
    {
        $types = [
            ['slug' => 'tourist',  'name_ru' => 'Туристическая',  'sort_order' => 1],
            ['slug' => 'student',  'name_ru' => 'Студенческая',   'sort_order' => 2],
            ['slug' => 'business', 'name_ru' => 'Бизнес-виза',    'sort_order' => 3],
        ];

        foreach ($types as $t) {
            DB::table('portal_visa_types')->updateOrInsert(
                ['slug' => $t['slug']],
                array_merge($t, [
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    // -------------------------------------------------------------------------
    // Страны (только где нужна виза для граждан Узбекистана)
    // -------------------------------------------------------------------------

    private function seedCountries(): void
    {
        // Удалить ОАЭ и другие безвизовые страны, если они уже были добавлены
        DB::table('portal_countries')->whereIn('country_code', ['AE'])->delete();

        $defaultVisa = json_encode(['tourist', 'student', 'business']);

        // Стандартные веса (сумма = 1.00)
        $w = [
            'weight_finance'  => 0.30,
            'weight_ties'     => 0.25,
            'weight_travel'   => 0.25,
            'weight_profile'  => 0.20,
            'min_monthly_income_usd' => 1000,
            'min_score'       => 60,
        ];

        // Веса для популярных направлений (уже были в БД — обновляем аккуратно)
        $detailed = [
            'DE' => ['weight_finance'=>0.35,'weight_ties'=>0.25,'weight_travel'=>0.25,'weight_profile'=>0.15,'min_monthly_income_usd'=>1500,'min_score'=>65,'sort_order'=>1],
            'ES' => ['weight_finance'=>0.30,'weight_ties'=>0.25,'weight_travel'=>0.25,'weight_profile'=>0.20,'min_monthly_income_usd'=>1200,'min_score'=>60,'sort_order'=>2],
            'FR' => ['weight_finance'=>0.35,'weight_ties'=>0.25,'weight_travel'=>0.20,'weight_profile'=>0.20,'min_monthly_income_usd'=>1500,'min_score'=>65,'sort_order'=>3],
            'IT' => ['weight_finance'=>0.30,'weight_ties'=>0.25,'weight_travel'=>0.25,'weight_profile'=>0.20,'min_monthly_income_usd'=>1200,'min_score'=>60,'sort_order'=>4],
            'PL' => ['weight_finance'=>0.25,'weight_ties'=>0.30,'weight_travel'=>0.25,'weight_profile'=>0.20,'min_monthly_income_usd'=>800,'min_score'=>55,'sort_order'=>5],
            'CZ' => ['weight_finance'=>0.25,'weight_ties'=>0.30,'weight_travel'=>0.25,'weight_profile'=>0.20,'min_monthly_income_usd'=>900,'min_score'=>55,'sort_order'=>6],
            'GB' => ['weight_finance'=>0.35,'weight_ties'=>0.25,'weight_travel'=>0.20,'weight_profile'=>0.20,'min_monthly_income_usd'=>2000,'min_score'=>70,'sort_order'=>7],
            'US' => ['weight_finance'=>0.40,'weight_ties'=>0.25,'weight_travel'=>0.15,'weight_profile'=>0.20,'min_monthly_income_usd'=>2000,'min_score'=>75,'sort_order'=>8],
            'CA' => ['weight_finance'=>0.35,'weight_ties'=>0.25,'weight_travel'=>0.20,'weight_profile'=>0.20,'min_monthly_income_usd'=>1800,'min_score'=>70,'sort_order'=>9],
            'KR' => ['weight_finance'=>0.30,'weight_ties'=>0.25,'weight_travel'=>0.25,'weight_profile'=>0.20,'min_monthly_income_usd'=>1000,'min_score'=>60,'sort_order'=>10],
        ];

        $countries = [
            // ── Шенген ──────────────────────────────────────────────────────
            ['country_code'=>'DE','name'=>'Германия',        'flag_emoji'=>'🇩🇪'],
            ['country_code'=>'AT','name'=>'Австрия',         'flag_emoji'=>'🇦🇹'],
            ['country_code'=>'BE','name'=>'Бельгия',         'flag_emoji'=>'🇧🇪'],
            ['country_code'=>'CH','name'=>'Швейцария',       'flag_emoji'=>'🇨🇭'],
            ['country_code'=>'CZ','name'=>'Чехия',           'flag_emoji'=>'🇨🇿'],
            ['country_code'=>'DK','name'=>'Дания',           'flag_emoji'=>'🇩🇰'],
            ['country_code'=>'EE','name'=>'Эстония',         'flag_emoji'=>'🇪🇪'],
            ['country_code'=>'ES','name'=>'Испания',         'flag_emoji'=>'🇪🇸'],
            ['country_code'=>'FI','name'=>'Финляндия',       'flag_emoji'=>'🇫🇮'],
            ['country_code'=>'FR','name'=>'Франция',         'flag_emoji'=>'🇫🇷'],
            ['country_code'=>'GR','name'=>'Греция',          'flag_emoji'=>'🇬🇷'],
            ['country_code'=>'HR','name'=>'Хорватия',        'flag_emoji'=>'🇭🇷'],
            ['country_code'=>'HU','name'=>'Венгрия',         'flag_emoji'=>'🇭🇺'],
            ['country_code'=>'IS','name'=>'Исландия',        'flag_emoji'=>'🇮🇸'],
            ['country_code'=>'IT','name'=>'Италия',          'flag_emoji'=>'🇮🇹'],
            ['country_code'=>'LI','name'=>'Лихтенштейн',    'flag_emoji'=>'🇱🇮'],
            ['country_code'=>'LT','name'=>'Литва',           'flag_emoji'=>'🇱🇹'],
            ['country_code'=>'LU','name'=>'Люксембург',      'flag_emoji'=>'🇱🇺'],
            ['country_code'=>'LV','name'=>'Латвия',          'flag_emoji'=>'🇱🇻'],
            ['country_code'=>'MT','name'=>'Мальта',          'flag_emoji'=>'🇲🇹'],
            ['country_code'=>'NL','name'=>'Нидерланды',      'flag_emoji'=>'🇳🇱'],
            ['country_code'=>'NO','name'=>'Норвегия',        'flag_emoji'=>'🇳🇴'],
            ['country_code'=>'PL','name'=>'Польша',          'flag_emoji'=>'🇵🇱'],
            ['country_code'=>'PT','name'=>'Португалия',      'flag_emoji'=>'🇵🇹'],
            ['country_code'=>'SE','name'=>'Швеция',          'flag_emoji'=>'🇸🇪'],
            ['country_code'=>'SI','name'=>'Словения',        'flag_emoji'=>'🇸🇮'],
            ['country_code'=>'SK','name'=>'Словакия',        'flag_emoji'=>'🇸🇰'],
            // ── Европа вне Шенгена (виза нужна) ────────────────────────────
            ['country_code'=>'GB','name'=>'Великобритания',  'flag_emoji'=>'🇬🇧'],
            ['country_code'=>'IE','name'=>'Ирландия',        'flag_emoji'=>'🇮🇪'],
            ['country_code'=>'BG','name'=>'Болгария',        'flag_emoji'=>'🇧🇬'],
            ['country_code'=>'RO','name'=>'Румыния',         'flag_emoji'=>'🇷🇴'],
            ['country_code'=>'CY','name'=>'Кипр',            'flag_emoji'=>'🇨🇾'],
            // ── Северная Америка ─────────────────────────────────────────────
            ['country_code'=>'US','name'=>'США',             'flag_emoji'=>'🇺🇸'],
            ['country_code'=>'CA','name'=>'Канада',          'flag_emoji'=>'🇨🇦'],
            ['country_code'=>'MX','name'=>'Мексика',         'flag_emoji'=>'🇲🇽'],
            // ── Азия ──────────────────────────────────────────────────────────
            ['country_code'=>'JP','name'=>'Япония',          'flag_emoji'=>'🇯🇵'],
            ['country_code'=>'KR','name'=>'Южная Корея',     'flag_emoji'=>'🇰🇷'],
            ['country_code'=>'CN','name'=>'Китай',           'flag_emoji'=>'🇨🇳'],
            ['country_code'=>'SG','name'=>'Сингапур',        'flag_emoji'=>'🇸🇬'],
            ['country_code'=>'IN','name'=>'Индия',           'flag_emoji'=>'🇮🇳'],
            ['country_code'=>'MY','name'=>'Малайзия',        'flag_emoji'=>'🇲🇾'],
            ['country_code'=>'VN','name'=>'Вьетнам',         'flag_emoji'=>'🇻🇳'],
            ['country_code'=>'TH','name'=>'Таиланд',         'flag_emoji'=>'🇹🇭'],
            // ── Океания ───────────────────────────────────────────────────────
            ['country_code'=>'AU','name'=>'Австралия',       'flag_emoji'=>'🇦🇺'],
            ['country_code'=>'NZ','name'=>'Новая Зеландия',  'flag_emoji'=>'🇳🇿'],
            // ── Африка ───────────────────────────────────────────────────────
            ['country_code'=>'ZA','name'=>'ЮАР',             'flag_emoji'=>'🇿🇦'],
        ];

        foreach ($countries as $i => $c) {
            $extra = $detailed[$c['country_code']] ?? array_merge($w, ['sort_order' => 20 + $i]);

            DB::table('portal_countries')->updateOrInsert(
                ['country_code' => $c['country_code']],
                array_merge($c, [
                    'is_active'              => true,
                    'visa_types'             => $defaultVisa,
                    'weight_finance'         => $extra['weight_finance'],
                    'weight_ties'            => $extra['weight_ties'],
                    'weight_travel'          => $extra['weight_travel'],
                    'weight_profile'         => $extra['weight_profile'],
                    'min_monthly_income_usd' => $extra['min_monthly_income_usd'],
                    'min_score'              => $extra['min_score'],
                    'sort_order'             => $extra['sort_order'],
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ])
            );
        }
    }
}
