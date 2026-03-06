<?php

namespace App\Http\Middleware;

use App\Modules\Payment\Models\AgencySubscription;
use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class CheckAgencyPlan
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if ($user?->role === 'superadmin') {
            return $next($request);
        }

        $agency = $user?->agency;

        if (! $agency) {
            return ApiResponse::forbidden('Агентство не найдено.');
        }

        // Проверяем активную подписку
        $subscription = AgencySubscription::where('agency_id', $agency->id)
            ->active()
            ->latest('starts_at')
            ->first();

        if ($subscription) {
            // Активационный сбор не оплачен — ограничиваем (но не блокируем billing-роуты)
            if ($subscription->needsActivationFee()) {
                $path = $request->path();
                if (! str_contains($path, 'billing')) {
                    return ApiResponse::forbidden('Необходимо оплатить активационный сбор для начала работы.');
                }
            }

            return $next($request);
        }

        // Фолбэк: проверяем план агентства напрямую
        if ($agency->isPlanActive()) {
            return $next($request);
        }

        return ApiResponse::forbidden('Ваш тарифный план истёк. Продлите подписку для продолжения работы.');
    }
}
