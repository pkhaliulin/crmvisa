<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->role === 'superadmin') {
                DB::statement("SET LOCAL app.is_superadmin = 'true'");
            }

            if ($user->agency_id) {
                DB::statement("SET LOCAL app.current_tenant_id = ?", [$user->agency_id]);
            }
        }

        return $next($request);
    }
}
