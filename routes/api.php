<?php

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\ClientAuthController;
use App\Modules\Case\Controllers\CaseController;
use App\Modules\Case\Controllers\DashboardController;
use App\Modules\Case\Controllers\KanbanController;
use App\Modules\Client\Controllers\ClientController;
use App\Modules\Client\Controllers\ClientPortalController;
use App\Modules\Document\Controllers\ChecklistController;
use App\Modules\Document\Controllers\DocumentController;
use App\Modules\Payment\Controllers\BillingController;
use App\Modules\Payment\Controllers\MarketplaceController;
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

        // Документы (вложены в заявку) — legacy загрузка без чек-листа
        Route::prefix('cases/{caseId}/documents')->group(function () {
            Route::get('/',           [DocumentController::class, 'index']);
            Route::post('/',          [DocumentController::class, 'store']);
            Route::patch('/{docId}',  [DocumentController::class, 'updateStatus']);
            Route::delete('/{docId}', [DocumentController::class, 'destroy']);
        });

        // Чек-лист документов
        Route::prefix('cases/{caseId}/checklist')->group(function () {
            Route::get('/',                        [ChecklistController::class, 'index']);
            Route::post('/',                       [ChecklistController::class, 'store']);
            Route::post('/{itemId}/upload',        [ChecklistController::class, 'upload']);
            Route::patch('/{itemId}/check',        [ChecklistController::class, 'check']);
            Route::patch('/{itemId}/review',       [ChecklistController::class, 'review']);
            Route::delete('/{itemId}',             [ChecklistController::class, 'destroy']);
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

    // -------------------------------------------------------------------------
    // Кабинет клиента
    // -------------------------------------------------------------------------

    // Авторизация клиента
    Route::prefix('client/auth')->middleware('throttle:10,1')->group(function () {
        Route::post('register', [ClientAuthController::class, 'register']);
        Route::post('login',    [ClientAuthController::class, 'login']);
    });
    Route::post('client/auth/logout', [ClientAuthController::class, 'logout'])->middleware('auth:api');

    // Личный кабинет (только role:client)
    Route::middleware(['auth:api', 'role:client'])->prefix('client/me')->group(function () {
        Route::get('/',                          [ClientPortalController::class, 'me']);
        Route::get('/journey',                   [ClientPortalController::class, 'journey']);
        Route::get('/case',                      [ClientPortalController::class, 'myCase']);
        Route::get('/checklist',                 [ClientPortalController::class, 'myChecklist']);
        Route::post('/checklist/{itemId}/upload', [ClientPortalController::class, 'uploadDocument']);
        Route::get('/scoring',                   [ClientPortalController::class, 'myScoring']);
    });

    // -------------------------------------------------------------------------
    // Биллинг
    // -------------------------------------------------------------------------

    // Публичный список тарифов
    Route::get('billing/plans', [BillingController::class, 'plans']);

    // Подписка и платежи (авторизован + активный план)
    Route::middleware(['auth:api', 'role:owner,superadmin', 'plan.active'])->group(function () {
        Route::get('billing/subscription',  [BillingController::class, 'subscription']);
        Route::get('billing/limits',        [BillingController::class, 'limits']);
        Route::get('billing/transactions',  [BillingController::class, 'transactions']);
        Route::post('billing/cancel',       [BillingController::class, 'cancel']);
    });

    // Суперадмин: ручная активация плана
    Route::middleware(['auth:api', 'role:superadmin'])->group(function () {
        Route::post('admin/billing/activate',      [BillingController::class, 'adminActivate']);
        Route::get('admin/marketplace/stats',      [MarketplaceController::class, 'adminStats']);
    });

    // -------------------------------------------------------------------------
    // Маркетплейс
    // -------------------------------------------------------------------------

    // Публичный (без авторизации)
    Route::get('marketplace',             [MarketplaceController::class, 'index']);
    Route::get('marketplace/{slug}',      [MarketplaceController::class, 'show']);
    Route::post('marketplace/{slug}/lead', [MarketplaceController::class, 'sendLead'])->middleware('throttle:5,1');

    // Агентства Pro/Enterprise: управление профилем и лидами
    Route::middleware(['auth:api', 'role:owner,superadmin', 'plan.active'])->group(function () {
        Route::get('agency/marketplace/profile',              [MarketplaceController::class, 'myProfile']);
        Route::put('agency/marketplace/profile',              [MarketplaceController::class, 'updateProfile']);
        Route::get('agency/marketplace/leads',                [MarketplaceController::class, 'leads']);
        Route::patch('agency/marketplace/leads/{id}/status',  [MarketplaceController::class, 'updateLeadStatus']);
    });

});
