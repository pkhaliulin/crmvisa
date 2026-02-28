<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Case\Controllers\CaseController;
use App\Modules\Client\Controllers\ClientController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth — публичные
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
    });

    // Auth — требуют токен
    Route::prefix('auth')->middleware('auth:api')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me',       [AuthController::class, 'me']);
    });

    // Защищённые роуты (JWT + роли)
    Route::middleware(['auth:api', 'role:owner,manager,superadmin'])->group(function () {

        // Клиенты
        Route::apiResource('clients', ClientController::class);

        // Заявки
        Route::get('cases/critical',          [CaseController::class, 'critical']);
        Route::post('cases/{id}/move-stage',  [CaseController::class, 'moveStage']);
        Route::apiResource('cases', CaseController::class);

    });

});
