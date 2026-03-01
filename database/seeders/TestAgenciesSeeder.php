<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestAgenciesSeeder extends Seeder
{
    // -------------------------------------------------------------------------
    // Пароли (все аккаунты)
    // -------------------------------------------------------------------------
    private const OWNER_PASS   = 'Owner@2026!';
    private const MANAGER_PASS = 'Manager@2026!';

    // -------------------------------------------------------------------------
    // Агентства
    // -------------------------------------------------------------------------
    private array $agencies = [
        [
            'name'        => 'Silk Road Visa',
            'slug'        => 'silk-road-visa',
            'email'       => 'info@silkroad-visa.uz',
            'phone'       => '+998712001001',
            'city'        => 'Ташкент',
            'key'         => 'silkroad',
            'plan'        => 'enterprise',
            'countries'   => ['DE', 'FR', 'IT'],
        ],
        [
            'name'        => 'Euro Visa Pro',
            'slug'        => 'euro-visa-pro',
            'email'       => 'info@euro-visa-pro.uz',
            'phone'       => '+998662002002',
            'city'        => 'Самарканд',
            'key'         => 'eurovisa',
            'plan'        => 'pro',
            'countries'   => ['GB', 'DE', 'TR'],
        ],
        [
            'name'        => 'Asia Passport',
            'slug'        => 'asia-passport',
            'email'       => 'info@asia-passport.uz',
            'phone'       => '+998652003003',
            'city'        => 'Бухара',
            'key'         => 'asiapass',
            'plan'        => 'enterprise',
            'countries'   => ['AE', 'TR', 'CN'],
        ],
        [
            'name'        => 'Visa Grand',
            'slug'        => 'visa-grand',
            'email'       => 'info@visa-grand.uz',
            'phone'       => '+998612004004',
            'city'        => 'Нукус',
            'key'         => 'visagrand',
            'plan'        => 'pro',
            'countries'   => ['US', 'GB', 'DE'],
        ],
        [
            'name'        => 'Travel Docs UZ',
            'slug'        => 'travel-docs-uz',
            'email'       => 'info@traveldocs.uz',
            'phone'       => '+998732005005',
            'city'        => 'Фергана',
            'key'         => 'traveldocs',
            'plan'        => 'pro',
            'countries'   => ['TR', 'AE', 'RU'],
        ],
    ];

    // -------------------------------------------------------------------------
    // Имена менеджеров (5 наборов × 5 агентств)
    // -------------------------------------------------------------------------
    private array $managerNames = [
        ['Алишер Каримов', 'Зулфия Назарова', 'Бобур Рахимов', 'Малика Эргашева', 'Шохрух Турсунов'],
        ['Санжар Холиқов', 'Нилуфар Хасанова', 'Тимур Азимов', 'Гулнора Юсупова', 'Баходир Умаров'],
        ['Сардор Исмоилов', 'Феруза Мамадалиева', 'Алишер Қодиров', 'Шахло Юнусова', 'Нодир Бахтиёров'],
        ['Музаффар Собиров', 'Мохира Чориева', 'Равшан Турсунов', 'Донийор Ражабов', 'Барно Хамидова'],
        ['Отабек Мирзаев', 'Улугбек Назаров', 'Сабрина Холматова', 'Фаридун Аскаров', 'Дилноза Ташматова'],
    ];

    // -------------------------------------------------------------------------
    // Клиенты (5 наборов × 5 агентств)
    // -------------------------------------------------------------------------
    private array $clients = [
        [
            ['name' => 'Камол Мирзаев',     'phone' => '+998901111001', 'nationality' => 'UZB', 'country' => 'DE', 'visa' => 'tourist'],
            ['name' => 'Дилноза Ташматова', 'phone' => '+998901111002', 'nationality' => 'UZB', 'country' => 'FR', 'visa' => 'student'],
            ['name' => 'Жасур Юсупов',      'phone' => '+998901111003', 'nationality' => 'UZB', 'country' => 'IT', 'visa' => 'tourist'],
            ['name' => 'Умида Мансурова',   'phone' => '+998901111004', 'nationality' => 'UZB', 'country' => 'DE', 'visa' => 'work'],
            ['name' => 'Лазиз Халиков',     'phone' => '+998901111005', 'nationality' => 'UZB', 'country' => 'FR', 'visa' => 'business'],
        ],
        [
            ['name' => 'Азиз Рустамов',      'phone' => '+998901112001', 'nationality' => 'UZB', 'country' => 'GB', 'visa' => 'tourist'],
            ['name' => 'Насиба Усманова',    'phone' => '+998901112002', 'nationality' => 'UZB', 'country' => 'DE', 'visa' => 'student'],
            ['name' => 'Фирдавс Бекмуродов','phone' => '+998901112003', 'nationality' => 'UZB', 'country' => 'TR', 'visa' => 'tourist'],
            ['name' => 'Шарифа Мухаммадова','phone' => '+998901112004', 'nationality' => 'UZB', 'country' => 'GB', 'visa' => 'work'],
            ['name' => 'Дониёр Эшматов',     'phone' => '+998901112005', 'nationality' => 'UZB', 'country' => 'TR', 'visa' => 'business'],
        ],
        [
            ['name' => 'Ойбек Сатторов',   'phone' => '+998901113001', 'nationality' => 'UZB', 'country' => 'AE', 'visa' => 'tourist'],
            ['name' => 'Зарнигор Алиева',  'phone' => '+998901113002', 'nationality' => 'UZB', 'country' => 'TR', 'visa' => 'student'],
            ['name' => 'Рустам Норматов',  'phone' => '+998901113003', 'nationality' => 'UZB', 'country' => 'CN', 'visa' => 'business'],
            ['name' => 'Хилола Турдиева',  'phone' => '+998901113004', 'nationality' => 'UZB', 'country' => 'AE', 'visa' => 'work'],
            ['name' => 'Сиддик Мирзаев',  'phone' => '+998901113005', 'nationality' => 'UZB', 'country' => 'TR', 'visa' => 'tourist'],
        ],
        [
            ['name' => 'Комилжон Холиков',  'phone' => '+998901114001', 'nationality' => 'UZB', 'country' => 'US', 'visa' => 'tourist'],
            ['name' => 'Матлуба Кенжаева',  'phone' => '+998901114002', 'nationality' => 'UZB', 'country' => 'GB', 'visa' => 'student'],
            ['name' => 'Бехзод Исмоилов',   'phone' => '+998901114003', 'nationality' => 'UZB', 'country' => 'DE', 'visa' => 'work'],
            ['name' => 'Гавхар Тоирова',    'phone' => '+998901114004', 'nationality' => 'UZB', 'country' => 'US', 'visa' => 'business'],
            ['name' => 'Шерзод Ортиков',    'phone' => '+998901114005', 'nationality' => 'UZB', 'country' => 'GB', 'visa' => 'tourist'],
        ],
        [
            ['name' => 'Акбар Норкулов',   'phone' => '+998901115001', 'nationality' => 'UZB', 'country' => 'TR', 'visa' => 'tourist'],
            ['name' => 'Муаззам Юлдашева', 'phone' => '+998901115002', 'nationality' => 'UZB', 'country' => 'AE', 'visa' => 'work'],
            ['name' => 'Ихтиер Очилов',    'phone' => '+998901115003', 'nationality' => 'UZB', 'country' => 'RU', 'visa' => 'business'],
            ['name' => 'Шахноза Холдорова','phone' => '+998901115004', 'nationality' => 'UZB', 'country' => 'TR', 'visa' => 'student'],
            ['name' => 'Мансур Тухтаев',   'phone' => '+998901115005', 'nationality' => 'UZB', 'country' => 'AE', 'visa' => 'tourist'],
        ],
    ];

    private array $stages = ['lead', 'qualification', 'documents', 'translation', 'appointment', 'review', 'result'];
    private array $priorities = ['low', 'normal', 'high', 'urgent'];

    public function run(): void
    {
        $now = now();

        foreach ($this->agencies as $ai => $agencyData) {
            // ------------------------------------------------------------------
            // Агентство
            // ------------------------------------------------------------------
            $agencyId = (string) Str::uuid();

            DB::table('agencies')->upsert([
                [
                    'id'                     => $agencyId,
                    'name'                   => $agencyData['name'],
                    'slug'                   => $agencyData['slug'],
                    'email'                  => $agencyData['email'],
                    'phone'                  => $agencyData['phone'],
                    'country'                => 'UZ',
                    'timezone'               => 'Asia/Tashkent',
                    'plan'                   => $agencyData['plan'],
                    'plan_expires_at'        => null,
                    'is_active'              => true,
                    'is_verified'            => true,
                    'commission_rate'        => 5.0,
                    'managers_see_all_cases' => false,
                    'lead_assignment_mode'   => 'round_robin',
                    'city'                   => $agencyData['city'],
                    'description'            => "Тестовое агентство: {$agencyData['name']}",
                    'experience_years'       => rand(3, 15),
                    'rating'                 => round(rand(38, 50) / 10, 1),
                    'reviews_count'          => rand(10, 120),
                    'created_at'             => $now,
                    'updated_at'             => $now,
                    'deleted_at'             => null,
                    'settings'               => null,
                ],
            ], ['slug'], [
                'name', 'email', 'phone', 'plan', 'is_active', 'is_verified',
                'city', 'description', 'updated_at',
            ]);

            // Получаем реальный id (могло быть upsert)
            $agencyId = DB::table('agencies')->where('slug', $agencyData['slug'])->value('id');

            // ------------------------------------------------------------------
            // Owner агентства
            // ------------------------------------------------------------------
            $key       = $agencyData['key'];
            $ownerEmail = "owner@{$key}.test";

            DB::table('users')->upsert([
                [
                    'id'         => (string) Str::uuid(),
                    'agency_id'  => $agencyId,
                    'name'       => "Директор {$agencyData['name']}",
                    'email'      => $ownerEmail,
                    'phone'      => null,
                    'password'   => Hash::make(self::OWNER_PASS),
                    'role'       => 'owner',
                    'is_active'  => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ],
            ], ['email'], ['name', 'password', 'role', 'agency_id', 'updated_at']);

            $ownerId = DB::table('users')->where('email', $ownerEmail)->value('id');

            // ------------------------------------------------------------------
            // 5 Менеджеров
            // ------------------------------------------------------------------
            $managerIds = [];
            foreach ($this->managerNames[$ai] as $mi => $mgrName) {
                $num      = $mi + 1;
                $mgrEmail = "m{$num}@{$key}.test";

                DB::table('users')->upsert([
                    [
                        'id'         => (string) Str::uuid(),
                        'agency_id'  => $agencyId,
                        'name'       => $mgrName,
                        'email'      => $mgrEmail,
                        'phone'      => null,
                        'password'   => Hash::make(self::MANAGER_PASS),
                        'role'       => 'manager',
                        'is_active'  => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                ], ['email'], ['name', 'password', 'role', 'agency_id', 'updated_at']);

                $managerIds[] = DB::table('users')->where('email', $mgrEmail)->value('id');
            }

            // ------------------------------------------------------------------
            // 5 Клиентов + 1 кейс каждому
            // ------------------------------------------------------------------
            $stageOptions = ['lead', 'qualification', 'documents', 'translation', 'appointment', 'review', 'result'];

            foreach ($this->clients[$ai] as $ci => $clientData) {
                $clientId = (string) Str::uuid();

                $existingClient = DB::table('clients')->where('phone', $clientData['phone'])->first();
                if (! $existingClient) {
                    DB::table('clients')->insert([
                        'id'          => $clientId,
                        'agency_id'   => $agencyId,
                        'name'        => $clientData['name'],
                        'phone'       => $clientData['phone'],
                        'email'       => null,
                        'nationality' => $clientData['nationality'],
                        'source'      => ['direct', 'referral', 'marketplace', 'other'][($ci + $ai) % 4],
                        'created_at'  => $now->copy()->subDays(rand(5, 60)),
                        'updated_at'  => $now,
                        'deleted_at'  => null,
                    ]);
                } else {
                    $clientId = $existingClient->id;
                }

                $clientId = DB::table('clients')->where('phone', $clientData['phone'])->value('id');

                // Кейс
                $stage      = $stageOptions[($ai + $ci * 2) % count($stageOptions)];
                $assignedTo = $managerIds[$ci % count($managerIds)];
                $createdAt  = $now->copy()->subDays(rand(3, 45));
                $priority   = $this->priorities[($ci + $ai) % 4];

                $criticalDate = null;
                if ($stage !== 'result') {
                    $criticalDate = $now->copy()->addDays(rand(-5, 30))->format('Y-m-d');
                }

                $existingCase = DB::table('cases')
                    ->where('agency_id', $agencyId)
                    ->where('client_id', $clientId)
                    ->first();

                if (! $existingCase) {
                    $caseId = (string) Str::uuid();

                    DB::table('cases')->insert([
                        'id'            => $caseId,
                        'agency_id'     => $agencyId,
                        'client_id'     => $clientId,
                        'assigned_to'   => $assignedTo,
                        'country_code'  => $clientData['country'],
                        'visa_type'     => $clientData['visa'],
                        'stage'         => $stage,
                        'priority'      => $priority,
                        'critical_date' => $criticalDate,
                        'travel_date'   => $criticalDate ? date('Y-m-d', strtotime($criticalDate . ' +14 days')) : null,
                        'notes'         => "Тестовый кейс {$clientData['name']}",
                        'created_at'    => $createdAt,
                        'updated_at'    => $now,
                        'deleted_at'    => null,
                    ]);

                    // История стадий (первые стадии до текущей)
                    $stageIndex = array_search($stage, $stageOptions);
                    $enteredAt  = $createdAt->copy();

                    for ($s = 0; $s <= $stageIndex; $s++) {
                        $isLast    = ($s === $stageIndex);
                        $exitedAt  = $isLast ? null : $enteredAt->copy()->addDays(rand(1, 5));
                        $stageSlug = $stageOptions[$s];

                        DB::table('case_stages')->insert([
                            'id'         => (string) Str::uuid(),
                            'case_id'    => $caseId,
                            'user_id'    => $assignedTo,
                            'stage'      => $stageSlug,
                            'entered_at' => $enteredAt,
                            'exited_at'  => $exitedAt,
                            'notes'      => null,
                            'sla_due_at' => null,
                            'is_overdue' => false,
                            'created_at' => $enteredAt,
                            'updated_at' => $now,
                        ]);

                        if ($exitedAt) {
                            $enteredAt = $exitedAt->copy()->addHours(1);
                        }
                    }
                }
            }

            // ------------------------------------------------------------------
            // Рабочие страны агентства
            // ------------------------------------------------------------------
            foreach ($agencyData['countries'] as $cc) {
                $exists = DB::table('agency_work_countries')
                    ->where('agency_id', $agencyId)
                    ->where('country_code', $cc)
                    ->exists();

                if (! $exists) {
                    DB::table('agency_work_countries')->insert([
                        'id'           => (string) Str::uuid(),
                        'agency_id'    => $agencyId,
                        'country_code' => $cc,
                        'visa_types'   => '[]',
                        'is_active'    => true,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                }
            }
        }
    }
}
