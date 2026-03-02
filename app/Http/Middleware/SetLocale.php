<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale')
            ?? $request->query('lang')
            ?? 'ru';

        if (in_array($locale, ['ru', 'uz'])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
