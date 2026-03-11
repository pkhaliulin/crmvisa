<?php

namespace App\Http\Middleware;

use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            Log::channel('security')->warning('Role check failed: insufficient permissions', [
                'ip'      => $request->ip(),
                'user_id' => $user?->id,
                'route'   => $request->path(),
                'reason'  => 'Required roles: [' . implode(', ', $roles) . '], actual: ' . ($user?->role ?? 'unauthenticated'),
            ]);

            return ApiResponse::forbidden('Insufficient permissions.');
        }

        return $next($request);
    }
}
