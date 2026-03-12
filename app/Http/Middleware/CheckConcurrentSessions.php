<?php

namespace App\Http\Middleware;

use App\Modules\Payment\Models\BillingPlan;
use App\Support\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class CheckConcurrentSessions
{
    /**
     * Проверяет что jwt_version токена совпадает с текущей версией в БД.
     * Для планов с max_concurrent_sessions = 1 (Trial/Starter) — только последний
     * логин активен, предыдущие сессии автоматически инвалидируются.
     *
     * Для планов с max_concurrent_sessions = 0 (безлимит) — проверка пропускается.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role === 'superadmin') {
            return $next($request);
        }

        $agency = $user->agency;
        if (! $agency) {
            return $next($request);
        }

        $planSlug = $agency->effectivePlan();
        $plan = BillingPlan::find($planSlug);

        // Если max_concurrent_sessions = 0 (безлимит) — пропускаем
        if (! $plan || $plan->max_concurrent_sessions === 0) {
            return $next($request);
        }

        // Проверяем jwt_version из токена vs из БД
        $payload = JWTAuth::getPayload();
        $tokenVersion = $payload->get('jwt_version');

        if ($tokenVersion !== null && (int) $tokenVersion !== (int) $user->jwt_version) {
            return ApiResponse::error(
                'Сессия завершена — выполнен вход с другого устройства. Ваш тариф позволяет только 1 одновременную сессию.',
                null,
                401
            );
        }

        return $next($request);
    }
}
