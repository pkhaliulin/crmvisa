<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token) {
            try {
                $payload = JWTAuth::setToken($token)->getPayload();
                $role     = $payload->get('role');
                $agencyId = $payload->get('agency_id');

                if ($role === 'superadmin') {
                    DB::statement("SET app.is_superadmin = 'true'");
                }

                if ($agencyId) {
                    DB::statement("SET app.current_tenant_id = ?", [(string) $agencyId]);
                }
            } catch (\Exception $e) {
                \Log::warning('SetTenantContext JWT parse failed', [
                    'error' => $e->getMessage(),
                    'token_prefix' => substr($token, 0, 20),
                ]);
            }
        }

        return $next($request);
    }
}
