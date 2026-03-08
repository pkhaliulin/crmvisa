<?php

namespace App\Support\Services;

use Illuminate\Support\Facades\Cache;

class AgencyCacheService
{
    private const DASHBOARD_TTL = 180;   // 3 минуты
    private const KANBAN_TTL = 120;      // 2 минуты
    private const FINANCIAL_TTL = 300;   // 5 минут

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public static function dashboardKey(string $agencyId, string $period = '30d'): string
    {
        return "dashboard:{$agencyId}:{$period}";
    }

    public static function getDashboard(string $agencyId, string $period, \Closure $callback): mixed
    {
        return Cache::remember(
            self::dashboardKey($agencyId, $period),
            self::DASHBOARD_TTL,
            $callback
        );
    }

    // -------------------------------------------------------------------------
    // Kanban
    // -------------------------------------------------------------------------

    public static function kanbanKey(string $agencyId, string $userId, string $role): string
    {
        // Owner видит все, менеджер — только свои
        $scope = in_array($role, ['owner', 'superadmin']) ? 'all' : $userId;
        return "kanban:{$agencyId}:{$scope}";
    }

    public static function getKanban(string $agencyId, string $userId, string $role, \Closure $callback): mixed
    {
        return Cache::remember(
            self::kanbanKey($agencyId, $userId, $role),
            self::KANBAN_TTL,
            $callback
        );
    }

    // -------------------------------------------------------------------------
    // Financial
    // -------------------------------------------------------------------------

    public static function financialKey(string $agencyId, string $period): string
    {
        return "financial:{$agencyId}:{$period}";
    }

    public static function getFinancial(string $agencyId, string $period, \Closure $callback): mixed
    {
        return Cache::remember(
            self::financialKey($agencyId, $period),
            self::FINANCIAL_TTL,
            $callback
        );
    }

    // -------------------------------------------------------------------------
    // Invalidation
    // -------------------------------------------------------------------------

    public static function invalidateAgency(string $agencyId): void
    {
        // Очищаем все кэши агентства по паттерну
        $prefixes = ['dashboard', 'kanban', 'financial'];

        foreach ($prefixes as $prefix) {
            // Используем tags если доступны, иначе forget по известным ключам
            self::forgetByPrefix("{$prefix}:{$agencyId}");
        }
    }

    public static function invalidateDashboard(string $agencyId): void
    {
        self::forgetByPrefix("dashboard:{$agencyId}");
        self::forgetByPrefix("kanban:{$agencyId}");
    }

    public static function invalidateFinancial(string $agencyId): void
    {
        self::forgetByPrefix("financial:{$agencyId}");
    }

    /**
     * Забываем кэш по префиксу. Redis поддерживает SCAN,
     * но для простоты используем известные period-ключи.
     */
    private static function forgetByPrefix(string $prefix): void
    {
        $store = Cache::getStore();

        // Redis: удаляем по паттерну
        if ($store instanceof \Illuminate\Cache\RedisStore) {
            $connection = $store->connection();
            $cachePrefix = config('cache.prefix', '') . ':';
            $keys = $connection->keys($cachePrefix . $prefix . ':*');
            if (!empty($keys)) {
                // Убираем системный префикс Redis
                $redisPrefix = config('database.redis.options.prefix', '');
                $cleanKeys = array_map(fn ($k) => str_replace($redisPrefix, '', $k), $keys);
                $connection->del($cleanKeys);
            }
            // Также удаляем точный ключ (если нет суффикса)
            $connection->del($cachePrefix . $prefix);
            return;
        }

        // Fallback для array/file: удаляем известные ключи
        $periods = ['1d', '3d', '7d', '30d', '60d', '90d', '365d', 'all', '2024', '2025', '2026'];
        foreach ($periods as $p) {
            Cache::forget("{$prefix}:{$p}");
        }
        Cache::forget($prefix);
    }
}
