<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\ClientAuthController;
use Illuminate\Support\Facades\Route;

// Auth -- публичные (защита от brute-force: 10 попыток в минуту)
Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// Верификация email (подписанная ссылка, без авторизации)
Route::get('auth/verify-email/{id}', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');

// Auth -- требуют токен
Route::prefix('auth')->middleware('auth:api')->group(function () {
    Route::post('logout',              [AuthController::class, 'logout']);
    Route::post('refresh',             [AuthController::class, 'refresh']);
    Route::get('me',                   [AuthController::class, 'me']);
    Route::post('resend-verification', [AuthController::class, 'resendVerification']);
});

// Авторизация клиента
Route::prefix('client/auth')->middleware('throttle:10,1')->group(function () {
    Route::post('register', [ClientAuthController::class, 'register']);
    Route::post('login',    [ClientAuthController::class, 'login']);
});
Route::post('client/auth/logout', [ClientAuthController::class, 'logout'])->middleware('auth:api');
