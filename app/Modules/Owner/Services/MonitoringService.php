<?php

namespace App\Modules\Owner\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class MonitoringService
{
    public function health(): array
    {
        // Database
        $dbStart = microtime(true);
        try {
            DB::select('SELECT 1');
            $dbMs = round((microtime(true) - $dbStart) * 1000);
            $dbStatus = 'ok';
        } catch (\Throwable $e) {
            $dbMs = null;
            $dbStatus = 'error';
        }

        // Cache
        try {
            Cache::put('_health_check', 'ok', 10);
            $cacheOk = Cache::get('_health_check') === 'ok';
            Cache::forget('_health_check');
            $cacheStatus = $cacheOk ? 'ok' : 'error';
        } catch (\Throwable) {
            $cacheStatus = 'error';
        }

        // Queue
        $pendingJobs = 0;
        try {
            $pendingJobs = DB::table('jobs')->count();
        } catch (\Throwable) {
            // Таблица jobs может не существовать
        }

        // Failed jobs
        $failedJobs = 0;
        try {
            $failedJobs = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
        }

        // Disk
        $storagePath = storage_path();
        $diskTotal = @disk_total_space($storagePath);
        $diskFree = @disk_free_space($storagePath);
        $diskUsagePercent = $diskTotal > 0
            ? round((1 - $diskFree / $diskTotal) * 100, 1)
            : null;

        // Memory
        $memoryUsageMb = round(memory_get_usage(true) / 1024 / 1024, 1);

        return [
            'database' => [
                'status' => $dbStatus,
                'response_ms' => $dbMs,
            ],
            'cache' => [
                'status' => $cacheStatus,
            ],
            'queue' => [
                'pending' => $pendingJobs,
            ],
            'failed_jobs' => [
                'count' => $failedJobs,
            ],
            'disk' => [
                'usage_percent' => $diskUsagePercent,
                'free_gb' => $diskFree ? round($diskFree / 1024 / 1024 / 1024, 1) : null,
            ],
            'memory' => [
                'usage_mb' => $memoryUsageMb,
            ],
        ];
    }

    public function errors(string $period = '24h'): array
    {
        $since = $this->periodToDate($period);

        // Ошибки 5xx из request_logs
        $serverErrors = DB::table('request_logs')
            ->select('path', 'status_code', DB::raw('COUNT(*) as count'), DB::raw('MAX(created_at) as last_at'))
            ->where('status_code', '>=', 500)
            ->where('created_at', '>=', $since)
            ->groupBy('path', 'status_code')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Последние строки с [ERROR] из laravel.log
        $logErrors = [];
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $lines = $this->tailFile($logPath, 500);
            $errorLines = [];
            foreach ($lines as $line) {
                if (str_contains($line, '.ERROR:') || str_contains($line, '[ERROR]')) {
                    $errorLines[] = $line;
                }
            }
            $logErrors = array_slice($errorLines, -20);
        }

        return [
            'server_errors' => $serverErrors,
            'log_errors' => $logErrors,
        ];
    }

    public function activity(string $period = '24h'): array
    {
        $since = $this->periodToDate($period);

        // Уникальные пользователи
        $uniqueUsers = DB::table('request_logs')
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        // Запросы по часам
        $hourly = DB::table('request_logs')
            ->select(DB::raw("date_trunc('hour', created_at) as hour"), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $since)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Топ эндпоинтов
        $topEndpoints = DB::table('request_logs')
            ->select('method', 'path', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $since)
            ->groupBy('method', 'path')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Последние действия из activity_log (spatie)
        $recentActivity = [];
        try {
            $recentActivity = DB::table('activity_log')
                ->select('description', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'created_at', 'properties')
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                ->map(function ($item) {
                    $item->properties = json_decode($item->properties, true);
                    return $item;
                });
        } catch (\Throwable) {
        }

        return [
            'unique_users' => $uniqueUsers,
            'hourly' => $hourly,
            'top_endpoints' => $topEndpoints,
            'recent_activity' => $recentActivity,
        ];
    }

    public function metrics(string $period = '24h'): array
    {
        $since = $this->periodToDate($period);

        // Response time stats
        $responseStats = DB::table('request_logs')
            ->select(
                DB::raw('ROUND(AVG(response_time_ms)) as avg_ms'),
                DB::raw('MAX(response_time_ms) as max_ms'),
                DB::raw('COUNT(*) as total_requests')
            )
            ->where('created_at', '>=', $since)
            ->first();

        // p95
        $p95 = DB::table('request_logs')
            ->where('created_at', '>=', $since)
            ->orderBy('response_time_ms')
            ->offset((int) (($responseStats->total_requests ?? 0) * 0.95))
            ->limit(1)
            ->value('response_time_ms');

        // RPM (текущая минута)
        $rpm = DB::table('request_logs')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
        $rpm = round($rpm / 5);

        // Распределение по status_code
        $statusDistribution = DB::table('request_logs')
            ->select(
                DB::raw("CASE
                    WHEN status_code BETWEEN 200 AND 299 THEN '2xx'
                    WHEN status_code BETWEEN 300 AND 399 THEN '3xx'
                    WHEN status_code BETWEEN 400 AND 499 THEN '4xx'
                    WHEN status_code >= 500 THEN '5xx'
                    ELSE 'other'
                END as status_group"),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', $since)
            ->groupBy('status_group')
            ->get()
            ->pluck('count', 'status_group');

        // Трафик по часам с разбивкой 2xx/5xx
        $trafficHourly = DB::table('request_logs')
            ->select(
                DB::raw("date_trunc('hour', created_at) as hour"),
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(*) FILTER (WHERE status_code BETWEEN 200 AND 299) as ok'),
                DB::raw('COUNT(*) FILTER (WHERE status_code >= 500) as errors')
            )
            ->where('created_at', '>=', $since)
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'response_time' => [
                'avg_ms' => (int) ($responseStats->avg_ms ?? 0),
                'p95_ms' => $p95 ?? 0,
                'max_ms' => $responseStats->max_ms ?? 0,
            ],
            'rpm' => $rpm,
            'total_requests' => $responseStats->total_requests ?? 0,
            'status_distribution' => $statusDistribution,
            'traffic_hourly' => $trafficHourly,
        ];
    }

    public function alerts(): array
    {
        $items = [];

        // 5xx за последний час
        $errors5xx = DB::table('request_logs')
            ->where('status_code', '>=', 500)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($errors5xx > 0) {
            $items[] = [
                'type' => 'error_5xx',
                'level' => $errors5xx > 10 ? 'critical' : 'warning',
                'message' => "Ошибок 5xx за последний час: {$errors5xx}",
                'value' => $errors5xx,
            ];
        }

        // Avg response time за 5 мин
        $avgResponse = DB::table('request_logs')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->avg('response_time_ms');

        if ($avgResponse && $avgResponse > 2000) {
            $items[] = [
                'type' => 'slow_response',
                'level' => $avgResponse > 5000 ? 'critical' : 'warning',
                'message' => "Среднее время ответа: " . round($avgResponse) . " мс",
                'value' => round($avgResponse),
            ];
        }

        // Failed jobs
        try {
            $failedCount = DB::table('failed_jobs')->count();
            if ($failedCount > 0) {
                $items[] = [
                    'type' => 'failed_jobs',
                    'level' => $failedCount > 10 ? 'critical' : 'warning',
                    'message' => "Failed jobs: {$failedCount}",
                    'value' => $failedCount,
                ];
            }
        } catch (\Throwable) {
        }

        // Queue pending
        try {
            $pendingCount = DB::table('jobs')->count();
            if ($pendingCount > 100) {
                $items[] = [
                    'type' => 'queue_backlog',
                    'level' => $pendingCount > 500 ? 'critical' : 'warning',
                    'message' => "В очереди: {$pendingCount} задач",
                    'value' => $pendingCount,
                ];
            }
        } catch (\Throwable) {
        }

        // Disk usage
        $storagePath = storage_path();
        $diskTotal = @disk_total_space($storagePath);
        $diskFree = @disk_free_space($storagePath);
        if ($diskTotal > 0) {
            $usagePercent = round((1 - $diskFree / $diskTotal) * 100, 1);
            if ($usagePercent > 90) {
                $items[] = [
                    'type' => 'disk_usage',
                    'level' => $usagePercent > 95 ? 'critical' : 'warning',
                    'message' => "Диск заполнен на {$usagePercent}%",
                    'value' => $usagePercent,
                ];
            }
        }

        // Определяем общий уровень
        $level = 'ok';
        foreach ($items as $item) {
            if ($item['level'] === 'critical') {
                $level = 'critical';
                break;
            }
            if ($item['level'] === 'warning') {
                $level = 'warning';
            }
        }

        return [
            'count' => count($items),
            'level' => $level,
            'items' => $items,
        ];
    }

    public function sentryIssues(string $period = '24h'): array
    {
        $token   = config('sentry.auth_token');
        $org     = config('sentry.org');
        $project = config('sentry.project');

        if (! $token || ! $org || ! $project) {
            return ['configured' => false, 'issues' => [], 'stats' => []];
        }

        $statsPeriod = match ($period) {
            '1h'  => '1h',
            '6h'  => '6h',
            '7d'  => '7d',
            '30d' => '30d',
            default => '24h',
        };

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->get("https://sentry.io/api/0/projects/{$org}/{$project}/issues/", [
                    'statsPeriod' => $statsPeriod,
                    'query'       => 'is:unresolved',
                    'sort'        => 'freq',
                    'limit'       => 25,
                ]);

            if (! $response->successful()) {
                return [
                    'configured' => true,
                    'error'      => 'Sentry API error: ' . $response->status(),
                    'issues'     => [],
                    'stats'      => [],
                ];
            }

            $issues = collect($response->json())->map(fn ($issue) => [
                'id'         => $issue['id'],
                'title'      => $issue['title'],
                'culprit'    => $issue['culprit'] ?? '',
                'level'      => $issue['level'] ?? 'error',
                'count'      => (int) ($issue['count'] ?? 0),
                'userCount'  => (int) ($issue['userCount'] ?? 0),
                'firstSeen'  => $issue['firstSeen'] ?? null,
                'lastSeen'   => $issue['lastSeen'] ?? null,
                'status'     => $issue['status'] ?? 'unresolved',
                'permalink'  => $issue['permalink'] ?? '',
                'shortId'    => $issue['shortId'] ?? '',
                'metadata'   => [
                    'type'  => $issue['metadata']['type'] ?? null,
                    'value' => $issue['metadata']['value'] ?? null,
                ],
            ])->all();

            // Статистика
            $totalEvents = collect($issues)->sum('count');
            $criticalCount = collect($issues)->where('level', 'fatal')->count();
            $errorCount = collect($issues)->where('level', 'error')->count();
            $warningCount = collect($issues)->where('level', 'warning')->count();

            return [
                'configured' => true,
                'issues'     => $issues,
                'stats'      => [
                    'total_issues'  => count($issues),
                    'total_events'  => $totalEvents,
                    'critical'      => $criticalCount,
                    'errors'        => $errorCount,
                    'warnings'      => $warningCount,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'configured' => true,
                'error'      => 'Sentry connection failed: ' . $e->getMessage(),
                'issues'     => [],
                'stats'      => [],
            ];
        }
    }

    public function queue(): array
    {
        // Pending по очередям
        $pending = [];
        try {
            $pending = DB::table('jobs')
                ->select('queue', DB::raw('COUNT(*) as count'))
                ->groupBy('queue')
                ->get();
        } catch (\Throwable) {
        }

        // Failed jobs
        $failed = [];
        try {
            $failed = DB::table('failed_jobs')
                ->select('id', 'uuid', 'queue', 'payload', 'exception', 'failed_at')
                ->orderByDesc('failed_at')
                ->limit(50)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    $job->job_name = $payload['displayName'] ?? 'Unknown';
                    $job->exception = mb_substr($job->exception, 0, 500);
                    unset($job->payload);
                    return $job;
                });
        } catch (\Throwable) {
        }

        return [
            'pending' => $pending,
            'failed' => $failed,
        ];
    }

    private function periodToDate(string $period): Carbon
    {
        return match ($period) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };
    }

    private function tailFile(string $path, int $lines = 500): array
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $start = max(0, $totalLines - $lines);
        $file->seek($start);

        $result = [];
        while (!$file->eof()) {
            $line = trim($file->current());
            if ($line !== '') {
                $result[] = $line;
            }
            $file->next();
        }

        return $result;
    }
}
