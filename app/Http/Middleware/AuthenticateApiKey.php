<?php

namespace App\Http\Middleware;

use App\Modules\Agency\Models\Agency;
use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token || ! str_starts_with($token, 'vbk_')) {
            Log::channel('security')->warning('API key auth failed: invalid format', [
                'ip'      => $request->ip(),
                'user_id' => null,
                'route'   => $request->path(),
                'reason'  => 'API key missing or invalid prefix',
            ]);

            return ApiResponse::error('API-ключ не предоставлен или имеет неверный формат.', null, 401);
        }

        $hash = hash('sha256', $token);

        $agency = Agency::where('api_key', $hash)->where('is_active', true)->first();

        if (! $agency) {
            Log::channel('security')->warning('API key auth failed: invalid key or inactive agency', [
                'ip'      => $request->ip(),
                'user_id' => null,
                'route'   => $request->path(),
                'reason'  => 'API key not found or agency deactivated',
            ]);

            return ApiResponse::error('Неверный API-ключ или агентство деактивировано.', null, 401);
        }

        // Устанавливаем tenant-контекст для RLS
        DB::statement('SET app.current_tenant_id = ?', [$agency->id]);

        // Сохраняем агентство в request для использования в контроллере
        $request->attributes->set('agency', $agency);
        $request->attributes->set('agency_id', $agency->id);

        return $next($request);
    }
}
