<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Support\Helpers\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class ValidateJwtAgency
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->role === 'superadmin') {
            return $next($request);
        }

        $token = $request->bearerToken();
        if (!$token) {
            return $next($request);
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return $next($request);
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $tokenAgencyId = $payload['agency_id'] ?? null;

        if ($tokenAgencyId && $user->agency_id && $tokenAgencyId !== $user->agency_id) {
            Log::channel('security')->warning('JWT agency_id mismatch', [
                'user_id' => $user->id,
                'user_agency' => $user->agency_id,
                'token_agency' => $tokenAgencyId,
                'ip' => $request->ip(),
            ]);
            return ApiResponse::forbidden('Token agency mismatch.');
        }

        return $next($request);
    }
}
