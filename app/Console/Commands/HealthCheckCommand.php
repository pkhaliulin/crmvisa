<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class HealthCheckCommand extends Command
{
    protected $signature = 'app:health-check
        {--fix : Автоматически исправлять найденные проблемы}
        {--json : Вывод результатов в JSON формате}
        {--section= : Запустить только указанную секцию (1-10)}';

    protected $description = 'Проверка целостности данных, API, RLS, производительности, очередей, дисков, SSL и безопасности.';

    private int $errors = 0;
    private int $warnings = 0;
    private int $fixed = 0;
    private array $jsonResults = [];
    private int $totalSections = 10;

    public function handle(): int
    {
        $section = $this->option('section') ? (int) $this->option('section') : null;
        $isJson = (bool) $this->option('json');

        if (!$isJson) {
            $this->info('=== VisaBor Health Check v2 ===');
            $this->newLine();
        }

        $checks = [
            1  => 'checkDatabase',
            2  => 'checkRlsPolicies',
            3  => 'checkDataIntegrity',
            4  => 'checkApiEndpoints',
            5  => 'checkPublicPortal',
            6  => 'checkPerformance',
            7  => 'checkQueueAndJobs',
            8  => 'checkDiskAndStorage',
            9  => 'checkSslAndExternalServices',
            10 => 'checkSessionsAndSecurity',
        ];

        foreach ($checks as $num => $method) {
            if ($section !== null && $section !== $num) {
                continue;
            }
            $this->$method();
        }

        if ($isJson) {
            $this->line(json_encode([
                'status' => $this->errors > 0 ? 'fail' : 'ok',
                'errors' => $this->errors,
                'warnings' => $this->warnings,
                'fixed' => $this->fixed,
                'checks' => $this->jsonResults,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->newLine();
            $this->info('=== Итого ===');
            $this->line("Ошибок: {$this->errors}");
            $this->line("Предупреждений: {$this->warnings}");
            if ($this->option('fix')) {
                $this->line("Исправлено: {$this->fixed}");
            }
        }

        return $this->errors > 0 ? 1 : 0;
    }

    // ──────────────────────────────────────────────
    // [1/10] База данных
    // ──────────────────────────────────────────────
    private function checkDatabase(): void
    {
        $this->sectionHeader(1, 'База данных');

        try {
            DB::select('SELECT 1');
            $this->ok('PostgreSQL подключение');
        } catch (\Exception $e) {
            $this->err('PostgreSQL недоступен: ' . $e->getMessage());
            return;
        }

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

        try {
            Cache::store('redis')->put('health_check', true, 5);
            Cache::store('redis')->forget('health_check');
            $this->ok('Redis подключение');
        } catch (\Exception $e) {
            $this->warnMsg('Redis недоступен: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    // [2/10] RLS политики
    // ──────────────────────────────────────────────
    private function checkRlsPolicies(): void
    {
        $this->sectionHeader(2, 'RLS политики');

        $rlsTables = [
            'cases', 'clients', 'users', 'case_checklist',
            'agency_work_countries', 'agency_service_packages',
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

        DB::statement("SET app.is_public_user = 'true'");
        $count = DB::table('agency_work_countries')->count();
        DB::statement("RESET app.is_public_user");

        if ($count === 0) {
            $this->err("agency_work_countries пуста при is_public_user=true — публичный портал не видит агентства");
        } else {
            $this->ok("agency_work_countries доступна для публичного портала ({$count} записей)");
        }
    }

    // ──────────────────────────────────────────────
    // [3/10] Целостность данных
    // ──────────────────────────────────────────────
    private function checkDataIntegrity(): void
    {
        $this->sectionHeader(3, 'Целостность данных');

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
                        DB::statement('SET LOCAL app.current_tenant_id = ?', [$a->id]);
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

    // ──────────────────────────────────────────────
    // [4/10] API эндпоинты (CRM)
    // ──────────────────────────────────────────────
    private function checkApiEndpoints(): void
    {
        $this->sectionHeader(4, 'API эндпоинты (CRM)');

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
            'GET /billing/subscription'  => '/api/v1/billing/subscription',
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

    // ──────────────────────────────────────────────
    // [5/10] Публичный портал
    // ──────────────────────────────────────────────
    private function checkPublicPortal(): void
    {
        $this->sectionHeader(5, 'Публичный портал');

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

    // ──────────────────────────────────────────────
    // [6/10] Производительность
    // ──────────────────────────────────────────────
    private function checkPerformance(): void
    {
        $this->sectionHeader(6, 'Производительность');

        // Проверка connection pool — количество активных подключений
        try {
            $connections = DB::select("
                SELECT count(*) as total,
                       count(*) FILTER (WHERE state = 'active') as active,
                       count(*) FILTER (WHERE state = 'idle') as idle,
                       count(*) FILTER (WHERE state = 'idle in transaction') as idle_in_tx
                FROM pg_stat_activity
                WHERE datname = current_database()
            ");
            if (!empty($connections)) {
                $c = $connections[0];
                $this->ok("DB подключения: всего={$c->total}, активные={$c->active}, idle={$c->idle}, idle_in_tx={$c->idle_in_tx}");

                if ($c->idle_in_tx > 5) {
                    $this->warnMsg("Много idle in transaction подключений ({$c->idle_in_tx}) — возможные утечки транзакций");
                }

                $maxConnections = DB::select("SHOW max_connections")[0]->max_connections;
                $usagePercent = round(($c->total / $maxConnections) * 100, 1);
                if ($usagePercent > 80) {
                    $this->err("Использование пула подключений: {$usagePercent}% ({$c->total}/{$maxConnections})");
                } else {
                    $this->ok("Пул подключений: {$usagePercent}% ({$c->total}/{$maxConnections})");
                }
            }
        } catch (\Exception $e) {
            $this->warnMsg('Не удалось проверить подключения: ' . $e->getMessage());
        }

        // Медленные запросы из pg_stat_statements (если расширение доступно)
        try {
            $hasPgStatStatements = DB::select("SELECT count(*) as cnt FROM pg_extension WHERE extname = 'pg_stat_statements'")[0]->cnt > 0;
            if ($hasPgStatStatements) {
                $slowQueries = DB::select("
                    SELECT query, calls, round(mean_exec_time::numeric, 2) as avg_ms, round(max_exec_time::numeric, 2) as max_ms
                    FROM pg_stat_statements
                    WHERE mean_exec_time > 1000
                    AND dbid = (SELECT oid FROM pg_database WHERE datname = current_database())
                    ORDER BY mean_exec_time DESC
                    LIMIT 3
                ");

                if (empty($slowQueries)) {
                    $this->ok('Нет запросов со средним временем > 1 сек');
                } else {
                    $this->warnMsg(count($slowQueries) . ' медленных запросов (среднее > 1 сек):');
                    foreach ($slowQueries as $sq) {
                        $shortQuery = mb_substr(preg_replace('/\s+/', ' ', $sq->query), 0, 80);
                        $this->line("    avg={$sq->avg_ms}ms max={$sq->max_ms}ms calls={$sq->calls} | {$shortQuery}...");
                    }
                }
            } else {
                // Проверяем лог файл на медленные запросы
                $logFile = storage_path('logs/laravel.log');
                if (file_exists($logFile)) {
                    $slowCount = 0;
                    $tailCmd = "tail -500 " . escapeshellarg($logFile) . " | grep -c 'slow_query\\|QueryException' 2>/dev/null";
                    $slowCount = (int) trim(shell_exec($tailCmd) ?? '0');

                    if ($slowCount > 0) {
                        $this->warnMsg("{$slowCount} записей о медленных запросах/ошибках в последних 500 строках лога");
                    } else {
                        $this->ok('Нет записей о медленных запросах в логе');
                    }
                }
            }
        } catch (\Exception $e) {
            $this->warnMsg('Не удалось проверить медленные запросы: ' . $e->getMessage());
        }

        // Среднее время API ответа из лога request_logs (если таблица есть)
        try {
            $hasRequestLogs = DB::select("SELECT to_regclass('public.request_logs') IS NOT NULL AS ok")[0]->ok;
            if ($hasRequestLogs) {
                $avgTime = DB::select("
                    SELECT
                        round(avg(response_time_ms)::numeric, 0) as avg_ms,
                        round(max(response_time_ms)::numeric, 0) as max_ms,
                        count(*) as cnt
                    FROM (
                        SELECT response_time_ms FROM request_logs
                        ORDER BY created_at DESC LIMIT 100
                    ) recent
                ");
                if (!empty($avgTime) && $avgTime[0]->cnt > 0) {
                    $avg = $avgTime[0]->avg_ms;
                    $max = $avgTime[0]->max_ms;
                    if ($avg > 500) {
                        $this->warnMsg("Среднее время API: {$avg}ms (макс: {$max}ms) — из последних {$avgTime[0]->cnt} запросов");
                    } else {
                        $this->ok("Среднее время API: {$avg}ms (макс: {$max}ms) — из последних {$avgTime[0]->cnt} запросов");
                    }
                }
            }
        } catch (\Exception $e) {
            // Таблица может не существовать — не критично
        }
    }

    // ──────────────────────────────────────────────
    // [7/10] Очереди и задачи
    // ──────────────────────────────────────────────
    private function checkQueueAndJobs(): void
    {
        $this->sectionHeader(7, 'Очереди и задачи');

        // Проверка воркера
        $workerRunning = false;
        exec('pgrep -f "queue:work" 2>/dev/null', $pids, $exitCode);
        if ($exitCode === 0 && !empty($pids)) {
            $this->ok('Queue worker запущен (PID: ' . implode(', ', $pids) . ')');
            $workerRunning = true;
        } else {
            // Попробуем Horizon
            exec('pgrep -f "horizon" 2>/dev/null', $horizonPids, $horizonExit);
            if ($horizonExit === 0 && !empty($horizonPids)) {
                $this->ok('Horizon запущен (PID: ' . implode(', ', $horizonPids) . ')');
                $workerRunning = true;
            } else {
                $this->warnMsg('Queue worker не запущен — задачи не обрабатываются');
            }
        }

        // Ожидающие задачи
        try {
            $hasJobsTable = DB::select("SELECT to_regclass('public.jobs') IS NOT NULL AS ok")[0]->ok;
            if ($hasJobsTable) {
                $pendingJobs = DB::table('jobs')->count();
                if ($pendingJobs > 100) {
                    $this->warnMsg("Ожидающих задач: {$pendingJobs} — очередь растет");
                } elseif ($pendingJobs > 0) {
                    $this->ok("Ожидающих задач: {$pendingJobs}");
                } else {
                    $this->ok('Очередь пуста');
                }
            }
        } catch (\Exception $e) {
            $this->warnMsg('Не удалось проверить таблицу jobs: ' . $e->getMessage());
        }

        // Неудачные задачи
        try {
            $hasFailedTable = DB::select("SELECT to_regclass('public.failed_jobs') IS NOT NULL AS ok")[0]->ok;
            if ($hasFailedTable) {
                $failedCount = DB::table('failed_jobs')->count();
                if ($failedCount > 0) {
                    $this->warnMsg("Неудачных задач: {$failedCount}");

                    // Последняя ошибка
                    $lastFailed = DB::table('failed_jobs')
                        ->orderByDesc('failed_at')
                        ->first();
                    if ($lastFailed) {
                        $errorPreview = mb_substr($lastFailed->exception ?? '', 0, 150);
                        $this->line("    Последняя ошибка ({$lastFailed->failed_at}): {$errorPreview}...");
                    }
                } else {
                    $this->ok('Нет неудачных задач');
                }
            }
        } catch (\Exception $e) {
            $this->warnMsg('Не удалось проверить failed_jobs: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    // [8/10] Диск и хранилище
    // ──────────────────────────────────────────────
    private function checkDiskAndStorage(): void
    {
        $this->sectionHeader(8, 'Диск и хранилище');

        // Проверка дискового пространства
        $partitions = ['/', '/var/backups'];
        foreach ($partitions as $partition) {
            $dfOutput = shell_exec("df -h " . escapeshellarg($partition) . " 2>/dev/null | tail -1");
            if ($dfOutput) {
                $parts = preg_split('/\s+/', trim($dfOutput));
                // Формат: Filesystem Size Used Avail Use% Mounted
                if (count($parts) >= 5) {
                    $usePercent = (int) str_replace('%', '', $parts[4]);
                    $avail = $parts[3];
                    $mounted = $parts[5] ?? $partition;

                    if ($usePercent > 90) {
                        $this->err("Диск {$mounted}: {$usePercent}% занято (свободно: {$avail}) — КРИТИЧНО");
                    } elseif ($usePercent > 80) {
                        $this->warnMsg("Диск {$mounted}: {$usePercent}% занято (свободно: {$avail})");
                    } else {
                        $this->ok("Диск {$mounted}: {$usePercent}% занято (свободно: {$avail})");
                    }
                }
            }
        }

        // Проверка записи в storage
        $storageDir = storage_path('logs');
        if (is_writable($storageDir)) {
            $this->ok('storage/logs доступен для записи');
        } else {
            $this->err('storage/logs НЕ доступен для записи');
        }

        $frameworkDir = storage_path('framework/cache');
        if (is_writable($frameworkDir)) {
            $this->ok('storage/framework/cache доступен для записи');
        } else {
            $this->err('storage/framework/cache НЕ доступен для записи');
        }

        // Размер директории загрузок
        $uploadDir = storage_path('app/documents');
        if (is_dir($uploadDir)) {
            $sizeOutput = trim(shell_exec("du -sh " . escapeshellarg($uploadDir) . " 2>/dev/null | cut -f1") ?? '0');
            $this->ok("Размер документов: {$sizeOutput}");
        } else {
            $this->warnMsg('Директория документов не найдена: ' . $uploadDir);
        }

        // Возраст последнего локального бэкапа (опционально — основные бэкапы через ServerCore)
        $backupDir = '/var/backups/crmvisa';
        if (is_dir($backupDir)) {
            $files = glob("{$backupDir}/db_*.sql.gz.enc");
            if (!empty($files)) {
                sort($files);
                $latest = end($files);
                $ageHours = round((time() - filemtime($latest)) / 3600, 1);
                $size = number_format(filesize($latest) / 1024 / 1024, 2);

                if ($ageHours > 25) {
                    $this->warnMsg("Локальный бэкап: {$ageHours}ч назад ({$size} MB) — RPO >25ч");
                } else {
                    $this->ok("Локальный бэкап: {$ageHours}ч назад ({$size} MB)");
                }
            } else {
                $this->ok('Локальные бэкапы не настроены (используется ServerCore: полный 1/7 + инкр. 6/7, 30 дней)');
            }
        } else {
            $this->ok('Локальные бэкапы не настроены (используется ServerCore: полный 1/7 + инкр. 6/7, 30 дней)');
        }
    }

    // ──────────────────────────────────────────────
    // [9/10] SSL и внешние сервисы
    // ──────────────────────────────────────────────
    private function checkSslAndExternalServices(): void
    {
        $this->sectionHeader(9, 'SSL и внешние сервисы');

        // Проверка SSL сертификатов
        $domains = ['visabor.uz', 'visacrm.uz'];
        foreach ($domains as $domain) {
            $cmd = "echo | openssl s_client -servername {$domain} -connect {$domain}:443 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null";
            $output = trim(shell_exec($cmd) ?? '');

            if (empty($output)) {
                $this->warnMsg("SSL {$domain}: не удалось проверить (домен недоступен или нет openssl)");
                continue;
            }

            // Формат: notAfter=Mar 15 12:00:00 2026 GMT
            if (preg_match('/notAfter=(.+)/', $output, $matches)) {
                $expiryDate = strtotime(trim($matches[1]));
                $daysLeft = round(($expiryDate - time()) / 86400);

                if ($daysLeft < 0) {
                    $this->err("SSL {$domain}: ИСТЕК " . abs($daysLeft) . " дней назад");
                } elseif ($daysLeft < 14) {
                    $this->err("SSL {$domain}: истекает через {$daysLeft} дней — СРОЧНО обновить");
                } elseif ($daysLeft < 30) {
                    $this->warnMsg("SSL {$domain}: истекает через {$daysLeft} дней");
                } else {
                    $this->ok("SSL {$domain}: действителен ещё {$daysLeft} дней");
                }
            }
        }

        // Eskiz SMS API
        try {
            $eskizResponse = Http::timeout(5)->get('https://notify.eskiz.uz/api');
            if ($eskizResponse->status() < 500) {
                $this->ok('Eskiz SMS API доступен (HTTP ' . $eskizResponse->status() . ')');
            } else {
                $this->warnMsg('Eskiz SMS API вернул ' . $eskizResponse->status());
            }
        } catch (\Exception $e) {
            $this->warnMsg('Eskiz SMS API недоступен: ' . $e->getMessage());
        }

        // Redis PING
        try {
            $pong = Redis::ping();
            $result = is_string($pong) ? $pong : (string) $pong;
            if (str_contains(strtolower($result), 'pong') || $result === '1' || $pong === true) {
                $this->ok('Redis PING: OK');
            } else {
                $this->warnMsg("Redis PING: неожиданный ответ ({$result})");
            }
        } catch (\Exception $e) {
            $this->warnMsg('Redis PING: ' . $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────
    // [10/10] Сессии и безопасность
    // ──────────────────────────────────────────────
    private function checkSessionsAndSecurity(): void
    {
        $this->sectionHeader(10, 'Сессии и безопасность');

        // Активные сессии (если используется database driver)
        try {
            $hasSessionsTable = DB::select("SELECT to_regclass('public.sessions') IS NOT NULL AS ok")[0]->ok;
            if ($hasSessionsTable) {
                $activeSessions = DB::table('sessions')
                    ->where('last_activity', '>', time() - 7200) // Последние 2 часа
                    ->count();
                $this->ok("Активных сессий (2ч): {$activeSessions}");
            } else {
                // Проверяем через personal_access_tokens
                $hasTokensTable = DB::select("SELECT to_regclass('public.personal_access_tokens') IS NOT NULL AS ok")[0]->ok;
                if ($hasTokensTable) {
                    $activeTokens = DB::table('personal_access_tokens')
                        ->where('last_used_at', '>', now()->subHours(2))
                        ->count();
                    $this->ok("Активных токенов (2ч): {$activeTokens}");
                }
            }
        } catch (\Exception $e) {
            $this->warnMsg('Не удалось проверить сессии: ' . $e->getMessage());
        }

        // Неудачные попытки входа из security лога
        $securityLog = storage_path('logs/security.log');
        if (file_exists($securityLog)) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $today = date('Y-m-d');
            $cmd = sprintf(
                'grep -c "login_failed\\|auth_failed\\|FAILED" %s 2>/dev/null',
                escapeshellarg($securityLog)
            );
            $failedCount = (int) trim(shell_exec($cmd) ?? '0');

            // Более точный подсчет за 24ч
            $cmd24h = sprintf(
                'grep -E "%s|%s" %s 2>/dev/null | grep -c "login_failed\\|auth_failed\\|FAILED"',
                $yesterday,
                $today,
                escapeshellarg($securityLog)
            );
            $failedCount24h = (int) trim(shell_exec($cmd24h) ?? '0');

            if ($failedCount24h > 50) {
                $this->err("Неудачных входов за 24ч: {$failedCount24h} — возможна брутфорс-атака");
            } elseif ($failedCount24h > 10) {
                $this->warnMsg("Неудачных входов за 24ч: {$failedCount24h}");
            } else {
                $this->ok("Неудачных входов за 24ч: {$failedCount24h}");
            }
        } else {
            $this->warnMsg('security.log не найден — логирование безопасности не настроено');
        }

        // Просроченные trial-агентства
        try {
            DB::statement("SET app.is_superadmin = 'true'");
            $expiredTrials = DB::select("
                SELECT count(*) as cnt FROM agencies
                WHERE deleted_at IS NULL
                AND is_active = true
                AND plan_expires_at IS NOT NULL
                AND plan_expires_at < NOW()
                AND plan = 'trial'
            ");
            $expiredCount = $expiredTrials[0]->cnt ?? 0;
            if ($expiredCount > 0) {
                $this->warnMsg("Просроченных trial-агентств: {$expiredCount} — рекомендуется деактивировать или уведомить");
            } else {
                $this->ok('Нет просроченных trial-агентств');
            }
            DB::statement("RESET app.is_superadmin");
        } catch (\Exception $e) {
            // Поле trial_ends_at может не существовать
            $this->warnMsg('Не удалось проверить trial-агентства: ' . $e->getMessage());
            try {
                DB::statement("RESET app.is_superadmin");
            } catch (\Exception) {
                // ignore
            }
        }
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    private function sectionHeader(int $num, string $title): void
    {
        if (!$this->option('json')) {
            $this->newLine();
            $this->info("[{$num}/{$this->totalSections}] {$title}");
        }
    }

    private function ok(string $msg): void
    {
        $this->addJsonResult('ok', $msg);
        if (!$this->option('json')) {
            $this->line("  <fg=green>OK</> {$msg}");
        }
    }

    private function err(string $msg): void
    {
        $this->errors++;
        $this->addJsonResult('error', $msg);
        if (!$this->option('json')) {
            $this->line("  <fg=red>ERR</> {$msg}");
        }
    }

    private function warnMsg(string $msg): void
    {
        $this->warnings++;
        $this->addJsonResult('warning', $msg);
        if (!$this->option('json')) {
            $this->line("  <fg=yellow>WARN</> {$msg}");
        }
    }

    private function addJsonResult(string $level, string $message): void
    {
        if ($this->option('json')) {
            $this->jsonResults[] = [
                'level' => $level,
                'message' => $message,
            ];
        }
    }
}
