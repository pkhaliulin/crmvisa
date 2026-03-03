<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // API rate limiter — 120 req/min для авторизованных, 60 для гостей
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            return $user
                ? Limit::perMinute(120)->by($user->id)
                : Limit::perMinute(60)->by($request->ip());
        });

        // Строгий лимит для auth endpoints
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Лимит для тяжёлых операций (отчёты, экспорт, пересчёт)
        RateLimiter::for('heavy', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?? $request->ip());
        });
    }
}
