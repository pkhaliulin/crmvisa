<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * GET /api/health — лёгкая проверка для UptimeRobot/Pingdom.
     * Без авторизации, без тяжёлых запросов.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // DB
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'fail';
            $healthy = false;
        }

        // Redis
        try {
            Redis::ping();
            $checks['redis'] = 'ok';
        } catch (\Throwable) {
            $checks['redis'] = 'fail';
            $healthy = false;
        }

        // Disk
        $freeBytes = disk_free_space(storage_path());
        $totalBytes = disk_total_space(storage_path());
        $freePct = $totalBytes > 0 ? round($freeBytes / $totalBytes * 100, 1) : 0;
        $checks['disk_free_pct'] = $freePct;
        if ($freePct < 10) {
            $healthy = false;
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
