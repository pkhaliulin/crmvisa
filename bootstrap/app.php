<?php

use App\Http\Middleware\CheckAgencyPlan;
use App\Http\Middleware\CheckFeature;
use App\Http\Middleware\CheckPlanLimits;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\LogApiRequest;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTenantContext;
use App\Modules\PublicPortal\Middleware\AuthPublicUser;
use App\Support\Helpers\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Глобальный middleware — security headers на все ответы
        $middleware->append(SecurityHeaders::class);

        // Алиасы для route middleware
        $middleware->alias([
            'role'           => CheckRole::class,
            'plan.active'    => CheckAgencyPlan::class,
            'auth.public'    => AuthPublicUser::class,
            'locale'         => SetLocale::class,
            'tenant.context' => SetTenantContext::class,
            'plan.limit'     => CheckPlanLimits::class,
            'feature'        => CheckFeature::class,
        ]);

        // Rate limiting на все API маршруты
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        // Установка tenant context для RLS (после аутентификации)
        $middleware->api(append: [
            SetTenantContext::class,
        ]);

        // Логирование всех API-запросов
        $middleware->api(append: [
            LogApiRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        // 401 Unauthenticated
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::unauthorized('Unauthenticated.');
            }
        });

        // 422 Validation
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::validationError($e->errors());
            }
        });

        // 404 Not Found
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::notFound('Resource not found.');
            }
        });

        // 429 Too Many Requests
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error('Too many requests. Try again later.', null, 429);
            }
        });
    })->create();
