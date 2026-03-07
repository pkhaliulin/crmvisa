<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class HealthCheckCommand extends Command
{
    protected $signature = 'app:health-check {--fix : Автоматически исправлять найденные проблемы}';
    protected $description = 'Проверка целостности данных, API и RLS. Находит баги до пользователя.';

    private int $errors = 0;
    private int $warnings = 0;
    private int $fixed = 0;

    public function handle(): int
    {
        $this->info('=== VisaBor Health Check ===');
        $this->newLine();

        $this->checkDatabase();
        $this->checkRlsPolicies();
        $this->checkDataIntegrity();
        $this->checkApiEndpoints();
        $this->checkPublicPortal();

        $this->newLine();
        $this->info('=== Итого ===');
        $this->line("Ошибок: {$this->errors}");
        $this->line("Предупреждений: {$this->warnings}");
        if ($this->option('fix')) {
            $this->line("Исправлено: {$this->fixed}");
        }

        return $this->errors > 0 ? 1 : 0;
    }

    private function checkDatabase(): void
    {
        $this->info('[1/5] База данных');

        // Проверка подключения
        try {
            DB::select('SELECT 1');
            $this->ok('PostgreSQL подключение');
        } catch (\Exception $e) {
            $this->err('PostgreSQL недоступен: ' . $e->getMessage());
            return;
        }

        // Проверка ключевых таблиц
        $tables = [
            'agencies', 'users', 'clients', 'cases', 'case_stages',
            'agency_work_countries', 'agency_service_packages', 'global_services',
            'portal_countries', 'public_users', 'billing_plans',
        ];
        foreach ($tables as $t) {
            $exists = DB::select("SELECT to_regclass('public.{$t}') IS NOT NULL AS ok")[0]->ok;
            if (!$exists) {
                $this->err("Таблица {$t} не существует");
            }
        }
        $this->ok('Все ключевые таблицы на месте');

        // Redis
        try {
            Cache::store('redis')->put('health_check', true, 5);
            Cache::store('redis')->forget('health_check');
            $this->ok('Redis подключение');
        } catch (\Exception $e) {
            $this->warnMsg('Redis недоступен: ' . $e->getMessage());
        }
    }

    private function checkRlsPolicies(): void
    {
        $this->newLine();
        $this->info('[2/5] RLS политики');

        $rlsTables = [
            'cases', 'clients', 'users', 'case_stages', 'case_checklist',
            'agency_work_countries', 'agency_service_packages', 'agency_service_package_items',
        ];

        foreach ($rlsTables as $table) {
            $rls = DB::select("SELECT relrowsecurity FROM pg_class WHERE relname = ?", [$table]);
            if (empty($rls) || !$rls[0]->relrowsecurity) {
                $this->warnMsg("RLS отключён на таблице {$table}");
                continue;
            }

            $policies = DB::select("SELECT policyname, qual FROM pg_policies WHERE tablename = ?", [$table]);
            if (empty($policies)) {
                $this->err("RLS включён на {$table}, но нет политик — таблица заблокирована!");
            }
        }

        // Проверка что публичные эндпоинты видят agency_work_countries
        DB::statement("SET app.is_public_user = 'true'");
        $count = DB::table('agency_work_countries')->count();
        DB::statement("RESET app.is_public_user");

        if ($count === 0) {
            $this->err("agency_work_countries пуста при is_public_user=true — публичный портал не видит агентства");
        } else {
            $this->ok("agency_work_countries доступна для публичного портала ({$count} записей)");
        }
    }

    private function checkDataIntegrity(): void
    {
        $this->newLine();
        $this->info('[3/5] Целостность данных');

        DB::statement("SET app.is_superadmin = 'true'");

        // Агентства без рабочих стран
        $agenciesWithoutCountries = DB::select("
            SELECT a.id, a.name FROM agencies a
            WHERE a.is_active = true AND a.deleted_at IS NULL
            AND NOT EXISTS (SELECT 1 FROM agency_work_countries awc WHERE awc.agency_id = a.id AND awc.is_active = true)
        ");
        foreach ($agenciesWithoutCountries as $a) {
            $this->warnMsg("Агентство '{$a->name}' без рабочих стран — не видно на маркетплейсе");

            if ($this->option('fix')) {
                // Заполнить из пакетов и кейсов
                $countryCodes = DB::table('agency_service_packages')
                    ->where('agency_id', $a->id)
                    ->distinct()
                    ->pluck('country_code')
                    ->merge(
                        DB::table('cases')
                            ->where('agency_id', $a->id)
                            ->whereNotNull('country_code')
                            ->distinct()
                            ->pluck('country_code')
                    )
                    ->unique();

                foreach ($countryCodes as $cc) {
                    DB::transaction(function () use ($a, $cc) {
                        DB::statement("SET LOCAL app.current_tenant_id = '{$a->id}'");
                        DB::table('agency_work_countries')->insertOrIgnore([
                            'id' => \Illuminate\Support\Str::uuid()->toString(),
                            'agency_id' => $a->id,
                            'country_code' => $cc,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    });
                }

                if ($countryCodes->isNotEmpty()) {
                    $this->fixed++;
                    $this->line("  -> Добавлено {$countryCodes->count()} стран для '{$a->name}'");
                }
            }
        }

        // Агентства без пакетов услуг
        $agenciesWithoutPackages = DB::select("
            SELECT a.id, a.name FROM agencies a
            WHERE a.is_active = true AND a.deleted_at IS NULL
            AND NOT EXISTS (SELECT 1 FROM agency_service_packages p WHERE p.agency_id = a.id AND p.is_active = true)
        ");
        foreach ($agenciesWithoutPackages as $a) {
            $this->warnMsg("Агентство '{$a->name}' без пакетов услуг — не видно на маркетплейсе");
        }

        // Заявки без клиента
        $orphanCases = DB::select("
            SELECT c.id, c.case_number FROM cases c
            WHERE c.client_id IS NULL AND c.deleted_at IS NULL
        ");
        if (count($orphanCases) > 0) {
            $this->err(count($orphanCases) . " заявок без клиента (client_id IS NULL)");
        }

        // Заявки без agency_id (кроме draft)
        $noAgencyCases = DB::select("
            SELECT c.id, c.case_number, c.public_status FROM cases c
            WHERE c.agency_id IS NULL AND c.deleted_at IS NULL
            AND c.public_status NOT IN ('draft', 'cancelled')
        ");
        if (count($noAgencyCases) > 0) {
            $this->warnMsg(count($noAgencyCases) . " активных заявок без agency_id");
        }

        // Пользователи без agency_id
        $orphanUsers = DB::select("
            SELECT u.id, u.name, u.email FROM users u
            WHERE u.agency_id IS NULL AND u.role != 'superadmin' AND u.deleted_at IS NULL
        ");
        if (count($orphanUsers) > 0) {
            $this->err(count($orphanUsers) . " пользователей без agency_id (не суперадмин)");
        }

        // portal_countries без записей
        $portalCount = DB::table('portal_countries')->where('is_active', true)->count();
        if ($portalCount === 0) {
            $this->err("portal_countries пуста — лендинг не покажет страны");
        } else {
            $this->ok("portal_countries: {$portalCount} активных стран");
        }

        // billing_plans без записей
        $plansCount = DB::table('billing_plans')->count();
        if ($plansCount === 0) {
            $this->err("billing_plans пуста — тарифы не работают");
        } else {
            $this->ok("billing_plans: {$plansCount} тарифов");
        }

        // global_services
        $servicesCount = DB::table('global_services')->count();
        if ($servicesCount === 0) {
            $this->warnMsg("global_services пуста — каталог услуг пуст");
        } else {
            $this->ok("global_services: {$servicesCount} услуг");
        }

        DB::statement("RESET app.is_superadmin");
    }

    private function checkApiEndpoints(): void
    {
        $this->newLine();
        $this->info('[4/5] API эндпоинты (CRM)');

        // Логинимся как тестовый owner
        $baseUrl = config('app.url');
        $token = null;

        try {
            $loginRes = Http::timeout(10)->post("{$baseUrl}/api/v1/auth/login", [
                'email' => 'owner@silkroad.test',
                'password' => 'Owner@2026!',
            ]);

            if ($loginRes->successful() && ($loginRes->json('data.access_token') || $loginRes->json('data.token'))) {
                $token = $loginRes->json('data.access_token') ?? $loginRes->json('data.token');
                $this->ok('Авторизация owner@silkroad.test');
            } else {
                $this->err('Не удалось авторизоваться: ' . $loginRes->body());
                return;
            }
        } catch (\Exception $e) {
            $this->err('Ошибка подключения к API: ' . $e->getMessage());
            return;
        }

        $endpoints = [
            'GET /dashboard'            => '/api/v1/dashboard',
            'GET /kanban'               => '/api/v1/kanban',
            'GET /cases'                => '/api/v1/cases',
            'GET /clients'              => '/api/v1/clients',
            'GET /users'                => '/api/v1/users',
            'GET /agency/settings'      => '/api/v1/agency/settings',
            'GET /agency/work-countries' => '/api/v1/agency/work-countries',
            'GET /agency/packages'      => '/api/v1/agency/packages',
            'GET /services'             => '/api/v1/services',
            'GET /billing/status'       => '/api/v1/billing/status',
            'GET /reports/overview'     => '/api/v1/reports/overview',
            'GET /reports/managers'     => '/api/v1/reports/managers',
            'GET /reports/countries'    => '/api/v1/reports/countries',
            'GET /reports/sla-performance' => '/api/v1/reports/sla-performance',
        ];

        foreach ($endpoints as $label => $url) {
            try {
                $res = Http::timeout(10)
                    ->withToken($token)
                    ->get("{$baseUrl}{$url}");

                if ($res->status() >= 500) {
                    $this->err("{$label} -> {$res->status()} Server Error");
                    $body = $res->json('message') ?? substr($res->body(), 0, 200);
                    $this->line("  -> {$body}");
                } elseif ($res->status() >= 400) {
                    $this->warnMsg("{$label} -> {$res->status()}");
                } else {
                    $this->ok("{$label} -> {$res->status()}");
                }
            } catch (\Exception $e) {
                $this->err("{$label} -> Timeout/Error: " . $e->getMessage());
            }
        }
    }

    private function checkPublicPortal(): void
    {
        $this->newLine();
        $this->info('[5/5] Публичный портал');

        $baseUrl = config('app.url');

        $publicEndpoints = [
            'GET /public/countries'         => '/api/v1/public/countries',
            'GET /public/served-countries'  => '/api/v1/public/served-countries',
            'GET /public/references'        => '/api/v1/public/references',
        ];

        foreach ($publicEndpoints as $label => $url) {
            try {
                $res = Http::timeout(10)->get("{$baseUrl}{$url}");

                if ($res->status() >= 500) {
                    $this->err("{$label} -> {$res->status()} Server Error");
                } else {
                    $data = $res->json('data');
                    $count = is_array($data) ? count($data) : (is_object($data) ? 1 : 0);
                    if ($count === 0 && $res->successful()) {
                        $this->warnMsg("{$label} -> 200 но пустые данные!");
                    } else {
                        $this->ok("{$label} -> {$res->status()} ({$count} записей)");
                    }
                }
            } catch (\Exception $e) {
                $this->err("{$label} -> " . $e->getMessage());
            }
        }

        // Проверяем что ES (популярная страна) показывает агентства
        try {
            $res = Http::timeout(10)->get("{$baseUrl}/api/v1/public/countries/ES");
            if ($res->successful()) {
                $hasAgencies = $res->json('data.has_agencies');
                $agenciesCount = $res->json('data.agencies_count', 0);
                if (!$hasAgencies || $agenciesCount === 0) {
                    $this->err("Испания (ES): has_agencies=false — маркетплейс сломан");
                } else {
                    $this->ok("Испания (ES): {$agenciesCount} агентств");
                }
            }
        } catch (\Exception $e) {
            $this->err("Испания (ES): " . $e->getMessage());
        }
    }

    // Helpers
    private function ok(string $msg): void
    {
        $this->line("  <fg=green>OK</> {$msg}");
    }

    private function err(string $msg): void
    {
        $this->errors++;
        $this->line("  <fg=red>ERR</> {$msg}");
    }

    private function warnMsg(string $msg): void
    {
        $this->warnings++;
        $this->line("  <fg=yellow>WARN</> {$msg}");
    }
}
