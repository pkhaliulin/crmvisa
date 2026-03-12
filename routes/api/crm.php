<?php

use App\Modules\Agency\Controllers\AgencySettingsController;
use App\Modules\LeadGen\Controllers\LeadChannelController;
use App\Modules\Agency\Controllers\ReportController;
use App\Modules\Case\Controllers\CaseController;
use App\Modules\Case\Controllers\CaseEngineController;
use App\Modules\Case\Controllers\DashboardController;
use App\Modules\Case\Controllers\KanbanController;
use App\Modules\Client\Controllers\ClientController;
use App\Modules\Client\Controllers\ClientPortalController;
use App\Modules\Document\Controllers\ChecklistController;
use App\Modules\Document\Controllers\DocumentController;
use App\Modules\Owner\Controllers\CountryDetailController;
use App\Modules\Owner\Controllers\OwnerController;
use App\Modules\Payment\Controllers\BillingController;
use App\Modules\Payment\Controllers\MarketplaceController;
use App\Modules\Scoring\Controllers\ScoringController;
use App\Modules\Service\Controllers\ServiceCatalogController;
use App\Modules\Task\Controllers\TaskController;
use App\Modules\User\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Защищённые роуты (JWT + роли + активный план + контроль сессий)
Route::middleware(['auth:api', 'role:owner,manager,superadmin', 'plan.active', 'session.limit'])->group(function () {

    // Клиенты
    Route::post('clients/parse-passport', [ClientController::class, 'parsePassport']);
    Route::apiResource('clients', ClientController::class)->middleware([
        'plan.limit:max_cases', // enforce на создание -- POST /clients
    ]);

    // Канбан
    Route::get('kanban', [KanbanController::class, 'board']);

    // Дашборд
    Route::get('dashboard',                        [DashboardController::class, 'index']);
    Route::get('dashboard/overdue',                [DashboardController::class, 'overdue']);
    Route::get('dashboard/managers/{id}/cases',    [DashboardController::class, 'managerCases']);
    Route::get('dashboard/goals',                  [DashboardController::class, 'goals']);
    Route::post('dashboard/goals',                 [DashboardController::class, 'saveGoal']);
    Route::get('dashboard/activity',               [DashboardController::class, 'activityFeed']);
    Route::get('dashboard/financial',              [DashboardController::class, 'financialSummary']);

    // Заявки
    Route::get('cases/critical',          [CaseController::class, 'critical']);
    Route::post('cases/{id}/move-stage',        [CaseController::class, 'moveStage']);
    Route::post('cases/{id}/complete',          [CaseController::class, 'complete']);
    Route::post('cases/{id}/submit-to-embassy', [CaseController::class, 'submitToEmbassy']);
    Route::post('cases/{id}/cancel',            [CaseController::class, 'cancel']);
    Route::patch('cases/{id}/expected-date',    [CaseController::class, 'updateExpectedDate']);
    Route::apiResource('cases', CaseController::class)->middleware([
        'plan.limit:max_cases',
    ]);

    // Документы (вложены в заявку)
    Route::prefix('cases/{caseId}/documents')->group(function () {
        Route::get('/',           [DocumentController::class, 'index']);
        Route::get('/zip',        [DocumentController::class, 'downloadZip']);
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
        Route::post('/{itemId}/upload-translation',   [ChecklistController::class, 'uploadTranslation']);
        Route::patch('/{itemId}/approve-translation',  [ChecklistController::class, 'approveTranslation']);
        Route::delete('/{itemId}',             [ChecklistController::class, 'destroy']);
    });

    // Visa Case Engine
    Route::prefix('cases/{id}/engine')->group(function () {
        Route::get('readiness',              [CaseEngineController::class, 'readiness']);
        Route::get('checkpoints',            [CaseEngineController::class, 'checkpoints']);
        Route::patch('checkpoints/{cpId}',   [CaseEngineController::class, 'toggleCheckpoint']);
        Route::get('form',                   [CaseEngineController::class, 'form']);
        Route::put('form/{step}',            [CaseEngineController::class, 'saveFormStep']);
        Route::post('form/prefill',          [CaseEngineController::class, 'prefillForm']);
        Route::get('form/progress',          [CaseEngineController::class, 'formProgress']);
        Route::post('init',                  [CaseEngineController::class, 'initialize']);
        Route::get('guidance',               [CaseEngineController::class, 'guidance']);
    });
    Route::get('engine/rules',                        [CaseEngineController::class, 'rules']);
    Route::get('engine/rules/{countryCode}/{visaType}',[CaseEngineController::class, 'ruleDetail']);

    // Задачи агентства
    Route::get('tasks/counters',           [TaskController::class, 'counters']);
    Route::post('tasks/{id}/transition',   [TaskController::class, 'transition']);
    Route::post('tasks/{id}/set-status',   [TaskController::class, 'setStatus']);
    Route::apiResource('tasks', TaskController::class);

    // Скоринг
    Route::get('scoring/countries',                        [ScoringController::class, 'countries']);
    Route::get('clients/{id}/profile',                     [ScoringController::class, 'getProfile']);
    Route::post('clients/{id}/profile',                    [ScoringController::class, 'saveProfile']);
    Route::get('clients/{id}/scoring',                     [ScoringController::class, 'scores']);
    Route::get('clients/{id}/scoring/recommendations',     [ScoringController::class, 'recommendations']);
    Route::post('clients/{id}/scoring/recalculate',        [ScoringController::class, 'recalculate'])->middleware('throttle:heavy');
    Route::get('clients/{id}/scoring/{country}',           [ScoringController::class, 'scoreByCountry']);

});

// Управление пользователями агентства (только owner)
Route::middleware(['auth:api', 'role:owner,superadmin', 'plan.active'])->group(function () {
    Route::get('users',                  [UserController::class, 'index']);
    Route::post('users',                 [UserController::class, 'store'])->middleware('plan.limit:max_managers');
    Route::get('users/{id}',             [UserController::class, 'show']);
    Route::patch('users/{id}',           [UserController::class, 'update']);
    Route::post('users/{id}/password',   [UserController::class, 'resetPassword']);
    Route::delete('users/{id}',          [UserController::class, 'destroy']);

    // Настройки агентства
    Route::get('agency/settings',                      [AgencySettingsController::class, 'show']);
    Route::patch('agency/settings',                    [AgencySettingsController::class, 'update']);
    Route::get('agency/work-countries',                [AgencySettingsController::class, 'workCountries']);
    Route::post('agency/work-countries',               [AgencySettingsController::class, 'addWorkCountry']);
    Route::delete('agency/work-countries/{cc}',        [AgencySettingsController::class, 'removeWorkCountry']);

    // API-ключ агентства для лидогенерации
    Route::post('agency/api-key',                     [AgencySettingsController::class, 'generateApiKey']);
    Route::get('agency/api-key',                      [AgencySettingsController::class, 'apiKeyInfo']);

    // Пакеты услуг агентства
    Route::get('agency/packages',          [ServiceCatalogController::class, 'myPackages']);
    Route::get('agency/packages/{id}',     [ServiceCatalogController::class, 'showPackage']);
    Route::post('agency/packages',         [ServiceCatalogController::class, 'store']);
    Route::patch('agency/packages/{id}',   [ServiceCatalogController::class, 'update']);
    Route::delete('agency/packages/{id}',  [ServiceCatalogController::class, 'destroy']);

    // Настройки уведомлений
    Route::get('notifications/settings', [\App\Modules\Notification\Controllers\NotificationSettingsController::class, 'index']);
    Route::put('notifications/settings/{eventType}', [\App\Modules\Notification\Controllers\NotificationSettingsController::class, 'update']);

    // Заметки агентства (БЗ)
    Route::prefix('knowledge/notes')->group(function () {
        Route::get('/',          [\App\Modules\Knowledge\Controllers\AgencyKnowledgeController::class, 'index']);
        Route::get('/{id}',      [\App\Modules\Knowledge\Controllers\AgencyKnowledgeController::class, 'show']);
        Route::post('/',         [\App\Modules\Knowledge\Controllers\AgencyKnowledgeController::class, 'store']);
        Route::patch('/{id}',    [\App\Modules\Knowledge\Controllers\AgencyKnowledgeController::class, 'update']);
        Route::delete('/{id}',   [\App\Modules\Knowledge\Controllers\AgencyKnowledgeController::class, 'destroy']);
        Route::post('/{id}/pin', [\App\Modules\Knowledge\Controllers\AgencyKnowledgeController::class, 'togglePin']);
        Route::post('/{id}/share', [\App\Modules\Knowledge\Controllers\AgencyKnowledgeController::class, 'share']);
    });

    // Глобальная БЗ (чтение, Enterprise)
    Route::get('knowledge/categories',        [\App\Modules\Knowledge\Controllers\PublicKnowledgeController::class, 'categories']);
    Route::get('knowledge/country/{code}',    [\App\Modules\Knowledge\Controllers\PublicKnowledgeController::class, 'byCountry']);
    Route::get('knowledge/{id}',              [\App\Modules\Knowledge\Controllers\PublicKnowledgeController::class, 'show']);
    Route::get('knowledge',                   [\App\Modules\Knowledge\Controllers\PublicKnowledgeController::class, 'index']);

    // Аналитика лидов
    Route::get('lead-analytics', [\App\Modules\LeadGen\Controllers\LeadAnalyticsController::class, 'index']);

    // Каналы лидогенерации
    Route::get('lead-channels',                    [LeadChannelController::class, 'index']);
    Route::get('lead-channels-connected',          [LeadChannelController::class, 'connected']);
    Route::get('lead-channels/stats',              [LeadChannelController::class, 'stats']);
    Route::get('lead-channels/{code}',             [LeadChannelController::class, 'show']);
    Route::post('lead-channels/{code}/track',      [LeadChannelController::class, 'trackAction']);
    Route::post('lead-channels/{code}/connect',    [LeadChannelController::class, 'connect']);
    Route::delete('lead-channels/{code}/disconnect', [LeadChannelController::class, 'disconnect']);

    // Отчёты (только owner, rate limited)
    Route::middleware('throttle:heavy')->group(function () {
        Route::get('reports/overview',         [ReportController::class, 'overview']);
        Route::get('reports/managers',         [ReportController::class, 'managers']);
        Route::get('reports/countries',        [ReportController::class, 'countries']);
        Route::get('reports/overdue',          [ReportController::class, 'overdue']);
        Route::get('reports/sla-performance',  [ReportController::class, 'slaPerformance']);
    });
});

// Глобальный каталог услуг + страны + типы виз (все авторизованные)
Route::middleware(['auth:api', 'role:owner,manager,superadmin', 'plan.active'])->group(function () {
    Route::get('services',   [ServiceCatalogController::class, 'index']);
    Route::get('countries',  [OwnerController::class, 'countries']);
    Route::get('visa-types', [OwnerController::class, 'visaTypes']);
    Route::get('references/all', [\App\Modules\Owner\Controllers\ReferenceController::class, 'all']);
    Route::get('countries/{code}/visa-settings', [CountryDetailController::class, 'visaSettingsPublic']);
});

// Личный кабинет клиента (только role:client)
Route::middleware(['auth:api', 'role:client'])->prefix('client/me')->group(function () {
    Route::get('/',                          [ClientPortalController::class, 'me']);
    Route::get('/journey',                   [ClientPortalController::class, 'journey']);
    Route::get('/case',                      [ClientPortalController::class, 'myCase']);
    Route::get('/checklist',                 [ClientPortalController::class, 'myChecklist']);
    Route::post('/checklist/{itemId}/upload', [ClientPortalController::class, 'uploadDocument']);
    Route::get('/scoring',                   [ClientPortalController::class, 'myScoring']);
});

// Биллинг -- публичный список тарифов
Route::get('billing/plans', [BillingController::class, 'plans']);

// Подписка и платежи (авторизован + активный план)
Route::middleware(['auth:api', 'role:owner,superadmin', 'plan.active'])->group(function () {
    Route::get('billing/subscription',  [BillingController::class, 'subscription']);
    Route::get('billing/limits',        [BillingController::class, 'limits']);
    Route::get('billing/wallet',        [BillingController::class, 'wallet']);
    Route::get('billing/transactions',  [BillingController::class, 'transactions']);
    Route::get('billing/invoices',      [BillingController::class, 'invoices']);
    Route::post('billing/cancel',           [BillingController::class, 'cancel']);
    Route::post('billing/change-plan',    [BillingController::class, 'changePlan']);
    Route::post('billing/cancel-downgrade', [BillingController::class, 'cancelDowngrade']);
});

// Агентства Pro/Enterprise: управление профилем и лидами маркетплейса
Route::middleware(['auth:api', 'role:owner,superadmin', 'plan.active'])->group(function () {
    Route::get('agency/marketplace/profile',              [MarketplaceController::class, 'myProfile']);
    Route::put('agency/marketplace/profile',              [MarketplaceController::class, 'updateProfile']);
    Route::get('agency/marketplace/leads',                [MarketplaceController::class, 'leads']);
    Route::patch('agency/marketplace/leads/{id}/status',  [MarketplaceController::class, 'updateLeadStatus']);
});
