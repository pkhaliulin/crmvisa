<?php

namespace App\Http\Middleware;

use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return ApiResponse::forbidden('Insufficient permissions.');
        }

        return $next($request);
    }
}
