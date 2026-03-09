<?php

use App\Modules\PublicPortal\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

// ===== SEO-оптимизированные Blade-страницы =====

// Главная (SSR для SEO)
Route::get('/', [LandingController::class, 'index'])->name('landing');

// SEO-страницы
Route::get('/country/{code}', [LandingController::class, 'country'])->name('country.show');
Route::get('/about', [LandingController::class, 'about'])->name('about');
Route::get('/privacy', [LandingController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LandingController::class, 'terms'])->name('terms');

// Sitemap
Route::get('/sitemap.xml', [LandingController::class, 'sitemap'])->name('sitemap');

// ===== Locale switch =====
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['ru', 'uz'])) {
        session(['locale' => $locale]);
        cookie()->queue('locale', $locale, 60 * 24 * 365);
    }
    return redirect()->back();
})->name('locale.switch');

// ===== Vue SPA — всё остальное =====
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
