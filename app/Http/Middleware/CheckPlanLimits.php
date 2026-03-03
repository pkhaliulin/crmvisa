<?php

namespace App\Http\Middleware;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    /**
     * Usage: middleware('plan.limit:max_cases') or middleware('plan.limit:max_managers')
     */
    public function handle(Request $request, Closure $next, string $limitType): Response
    {
        $user = $request->user();

        if (! $user || $user->role === 'superadmin') {
            return $next($request);
        }

        $agency = $user->agency;
        if (! $agency) {
            return $next($request);
        }

        $plan = $agency->plan;
        if (! $plan) {
            return $next($request);
        }

        return match ($limitType) {
            'max_cases'    => $this->checkCases($agency, $plan, $next, $request),
            'max_managers' => $this->checkManagers($agency, $plan, $next, $request),
            default        => $next($request),
        };
    }

    private function checkCases(Agency $agency, $plan, Closure $next, Request $request): Response
    {
        $maxCases = $plan->maxCases();

        if ($maxCases >= PHP_INT_MAX) {
            return $next($request);
        }

        $activeCount = VisaCase::withoutGlobalScopes()
            ->where('agency_id', $agency->id)
            ->whereNotIn('stage', ['result'])
            ->count();

        if ($activeCount >= $maxCases) {
            return ApiResponse::forbidden(
                "Лимит активных заявок исчерпан ({$maxCases}). Перейдите на более высокий тариф."
            );
        }

        return $next($request);
    }

    private function checkManagers(Agency $agency, $plan, Closure $next, Request $request): Response
    {
        $maxManagers = $plan->maxManagers();

        if ($maxManagers >= PHP_INT_MAX) {
            return $next($request);
        }

        $managersCount = User::withoutGlobalScopes()
            ->where('agency_id', $agency->id)
            ->whereIn('role', ['owner', 'manager'])
            ->whereNull('deleted_at')
            ->count();

        if ($managersCount >= $maxManagers) {
            return ApiResponse::forbidden(
                "Лимит менеджеров исчерпан ({$maxManagers}). Перейдите на более высокий тариф."
            );
        }

        return $next($request);
    }
}
