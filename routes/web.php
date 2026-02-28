<?php

use Illuminate\Support\Facades\Route;

// SPA — все non-API маршруты отдают Vue приложение
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');
