<?php

namespace App\Http\Middleware;

use App\Support\Facades\Feature;
use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeature
{
    /**
     * Usage: middleware('feature:marketplace') or middleware('feature:scoring_v2')
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        // Superadmin всегда имеет доступ
        if ($user?->role === 'superadmin') {
            return $next($request);
        }

        $agency = $user?->agency;

        if (! Feature::isEnabled($featureKey, $agency)) {
            return ApiResponse::forbidden(
                "Feature '{$featureKey}' is not available for your plan."
            );
        }

        return $next($request);
    }
}
