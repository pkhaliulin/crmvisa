<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Case\Controllers\CaseController;
use App\Modules\Case\Controllers\DashboardController;
use App\Modules\Case\Controllers\KanbanController;
use App\Modules\Client\Controllers\ClientController;
use App\Modules\Document\Controllers\DocumentController;
use App\Modules\Scoring\Controllers\ScoringController;
use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth — публичные (защита от brute-force: 10 попыток в минуту)
    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
    });

    // Auth — требуют токен
    Route::prefix('auth')->middleware('auth:api')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me',       [AuthController::class, 'me']);
    });

    // Защищённые роуты (JWT + роли + активный план)
    Route::middleware(['auth:api', 'role:owner,manager,superadmin', 'plan.active'])->group(function () {

        // Клиенты
        Route::apiResource('clients', ClientController::class);

        // Канбан
        Route::get('kanban', [KanbanController::class, 'board']);

        // Дашборд
        Route::get('dashboard',                        [DashboardController::class, 'index']);
        Route::get('dashboard/overdue',                [DashboardController::class, 'overdue']);
        Route::get('dashboard/managers/{id}/cases',    [DashboardController::class, 'managerCases']);

        // Заявки
        Route::get('cases/critical',          [CaseController::class, 'critical']);
        Route::post('cases/{id}/move-stage',  [CaseController::class, 'moveStage']);
        Route::apiResource('cases', CaseController::class);

        // Документы (вложены в заявку)
        Route::prefix('cases/{caseId}/documents')->group(function () {
            Route::get('/',           [DocumentController::class, 'index']);
            Route::post('/',          [DocumentController::class, 'store']);
            Route::patch('/{docId}',  [DocumentController::class, 'updateStatus']);
            Route::delete('/{docId}', [DocumentController::class, 'destroy']);
        });

        // Скоринг
        Route::get('scoring/countries',                   [ScoringController::class, 'countries']);
        Route::get('clients/{id}/profile',                [ScoringController::class, 'getProfile']);
        Route::post('clients/{id}/profile',               [ScoringController::class, 'saveProfile']);
        Route::get('clients/{id}/scoring',                [ScoringController::class, 'scores']);
        Route::post('clients/{id}/scoring/recalculate',   [ScoringController::class, 'recalculate']);
        Route::get('clients/{id}/scoring/{country}',      [ScoringController::class, 'scoreByCountry']);

    });

    // Управление пользователями агентства (только owner)
    Route::middleware(['auth:api', 'role:owner,superadmin', 'plan.active'])->group(function () {
        Route::get('users',          [UserController::class, 'index']);
        Route::post('users',         [UserController::class, 'store']);
        Route::patch('users/{id}',   [UserController::class, 'update']);
        Route::delete('users/{id}',  [UserController::class, 'destroy']);
    });

});
