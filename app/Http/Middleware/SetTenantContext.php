<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token) {
            $payload = $this->decodeJwtPayload($token);

            if ($payload) {
                $role     = $payload['role'] ?? null;
                $agencyId = $payload['agency_id'] ?? null;

                if ($agencyId && ! preg_match('/^[0-9a-f\-]{36}$/i', $agencyId)) {
                    Log::channel('security')->warning('Invalid UUID in JWT payload', [
                        'ip'      => $request->ip(),
                        'user_id' => $payload['sub'] ?? null,
                        'route'   => $request->path(),
                        'reason'  => 'agency_id failed UUID regex: ' . substr((string) $agencyId, 0, 50),
                    ]);
                }

                if (DB::connection()->getDriverName() !== 'sqlite') {
                    if ($role === 'superadmin') {
                        DB::statement("SET app.is_superadmin = 'true'");
                    }

                    if ($agencyId && preg_match('/^[0-9a-f\-]{36}$/i', $agencyId)) {
                        DB::statement('SET app.current_tenant_id = ?', [$agencyId]);
                    }
                }
            }
        }

        return $next($request);
    }

    /**
     * Декодирует payload JWT без валидации подписи.
     * Подпись проверит auth middleware позже.
     */
    private function decodeJwtPayload(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(
            base64_decode(strtr($parts[1], '-_', '+/')),
            true
        );

        return is_array($payload) ? $payload : null;
    }
}
