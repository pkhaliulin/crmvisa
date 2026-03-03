<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        // Не логируем мониторинг (чтобы не мониторил сам себя)
        if (str_starts_with($request->path(), 'api/v1/owner/monitoring')) {
            return $response;
        }

        $responseTimeMs = (int) round((microtime(true) - $start) * 1000);

        try {
            DB::table('request_logs')->insert([
                'user_id'           => $request->user()?->id,
                'method'            => $request->method(),
                'path'              => '/' . ltrim($request->path(), '/'),
                'status_code'       => $response->getStatusCode(),
                'response_time_ms'  => $responseTimeMs,
                'ip'                => $request->ip(),
                'user_agent'        => mb_substr($request->userAgent() ?? '', 0, 500) ?: null,
                'request_body_size' => $request->header('Content-Length')
                    ? (int) $request->header('Content-Length')
                    : ($request->getContent() ? strlen($request->getContent()) : null),
                'created_at'        => now(),
            ]);
        } catch (\Throwable) {
            // Не ломаем приложение из-за логирования
        }

        return $response;
    }
}
