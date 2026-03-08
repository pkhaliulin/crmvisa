<?php

namespace App\Providers;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Policies\CasePolicy;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Policies\ClientPolicy;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Policies\DocumentPolicy;
use App\Modules\User\Models\User;
use App\Modules\User\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
        Gate::policy(VisaCase::class, CasePolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
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

        // Логирование медленных SQL запросов (>500ms)
        if (app()->environment('production')) {
            DB::listen(function (QueryExecuted $query) {
                if ($query->time > 500) {
                    Log::channel('slow_queries')->warning('Slow query', [
                        'sql' => $query->sql,
                        'time_ms' => round($query->time, 2),
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }
    }
}
