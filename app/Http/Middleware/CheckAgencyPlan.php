<?php

namespace App\Http\Middleware;

use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class CheckAgencyPlan
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user   = $request->user();
        $agency = $user?->agency;

        if (! $agency || ! $agency->isPlanActive()) {
            return ApiResponse::forbidden('Your agency plan has expired. Please renew to continue.');
        }

        return $next($request);
    }
}
