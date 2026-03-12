<?php

namespace App\Http\Middleware;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Payment\Models\BillingPlan;
use App\Modules\User\Models\User;
use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanLimits
{
    /**
     * Usage: middleware('plan.limit:max_cases') or middleware('plan.limit:max_managers')
     *
     * Все лимиты берутся из billing_plans (SSOT), а не из Plan enum.
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

        $billingPlan = $this->resolveBillingPlan($agency);
        if (! $billingPlan) {
            return $next($request);
        }

        return match ($limitType) {
            'max_cases'    => $this->checkCases($agency, $billingPlan, $next, $request),
            'max_managers' => $this->checkManagers($agency, $billingPlan, $next, $request),
            default        => $next($request),
        };
    }

    private function resolveBillingPlan(Agency $agency): ?BillingPlan
    {
        $planSlug = $agency->effectivePlan();

        return BillingPlan::find($planSlug);
    }

    private function checkCases(Agency $agency, BillingPlan $plan, Closure $next, Request $request): Response
    {
        $maxCases = $plan->max_cases;

        if ($maxCases === 0) {
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

    private function checkManagers(Agency $agency, BillingPlan $plan, Closure $next, Request $request): Response
    {
        $maxManagers = $plan->max_managers;

        if ($maxManagers === 0) {
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
