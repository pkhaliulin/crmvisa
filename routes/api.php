<?php

use App\Modules\Agency\Controllers\AgencySettingsController;
use App\Modules\Agency\Controllers\ReportController;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\ClientAuthController;
use App\Modules\Case\Controllers\CaseController;
use App\Modules\Case\Controllers\DashboardController;
use App\Modules\Case\Controllers\KanbanController;
use App\Modules\Client\Controllers\ClientController;
use App\Modules\Client\Controllers\ClientPortalController;
use App\Modules\PublicPortal\Controllers\PublicAuthController;
use App\Modules\PublicPortal\Controllers\PublicProfileController;
use App\Modules\PublicPortal\Controllers\PublicScoringController;
use App\Modules\Document\Controllers\ChecklistController;
use App\Modules\Document\Controllers\CountryRequirementController;
use App\Modules\Document\Controllers\DocumentController;
use App\Modules\Document\Controllers\DocumentTemplateController;
use App\Modules\Payment\Controllers\BillingController;
use App\Modules\Payment\Controllers\MarketplaceController;
use App\Modules\Scoring\Controllers\ScoringController;
use App\Modules\Service\Controllers\ServiceCatalogController;
use App\Modules\Owner\Controllers\OwnerController;
use App\Modules\TelegramBot\Controllers\TelegramBotController;
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

        // Настройки агентства
        Route::get('agency/settings',                      [AgencySettingsController::class, 'show']);
        Route::patch('agency/settings',                    [AgencySettingsController::class, 'update']);
        Route::get('agency/work-countries',                [AgencySettingsController::class, 'workCountries']);
        Route::post('agency/work-countries',               [AgencySettingsController::class, 'addWorkCountry']);
        Route::delete('agency/work-countries/{cc}',        [AgencySettingsController::class, 'removeWorkCountry']);

        // Пакеты услуг агентства
        Route::get('agency/packages',          [ServiceCatalogController::class, 'myPackages']);
        Route::post('agency/packages',         [ServiceCatalogController::class, 'store']);
        Route::patch('agency/packages/{id}',   [ServiceCatalogController::class, 'update']);
        Route::delete('agency/packages/{id}',  [ServiceCatalogController::class, 'destroy']);

        // Отчёты (только owner)
        Route::get('reports/overview',         [ReportController::class, 'overview']);
        Route::get('reports/managers',         [ReportController::class, 'managers']);
        Route::get('reports/countries',        [ReportController::class, 'countries']);
        Route::get('reports/overdue',          [ReportController::class, 'overdue']);
        Route::get('reports/sla-performance',  [ReportController::class, 'slaPerformance']);
    });

    // Глобальный каталог услуг + страны + типы виз (все авторизованные)
    Route::middleware(['auth:api', 'role:owner,manager,superadmin', 'plan.active'])->group(function () {
        Route::get('services',   [ServiceCatalogController::class, 'index']);
        Route::get('countries',  [OwnerController::class, 'countries']);
        Route::get('visa-types', [OwnerController::class, 'visaTypes']);
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

    // Суперадмин: ручная активация плана + управление справочниками документов
    Route::middleware(['auth:api', 'role:superadmin'])->group(function () {
        Route::post('admin/billing/activate',      [BillingController::class, 'adminActivate']);
        Route::get('admin/marketplace/stats',      [MarketplaceController::class, 'adminStats']);

        // Справочник шаблонов документов
        Route::get('admin/document-templates',         [DocumentTemplateController::class, 'index']);
        Route::post('admin/document-templates',        [DocumentTemplateController::class, 'store']);
        Route::patch('admin/document-templates/{id}',  [DocumentTemplateController::class, 'update']);
        Route::delete('admin/document-templates/{id}', [DocumentTemplateController::class, 'destroy']);

        // Требования стран к документам
        Route::get('admin/country-requirements',                [CountryRequirementController::class, 'index']);
        Route::get('admin/country-requirements/countries',      [CountryRequirementController::class, 'countries']);
        Route::post('admin/country-requirements',               [CountryRequirementController::class, 'store']);
        Route::patch('admin/country-requirements/{id}',         [CountryRequirementController::class, 'update']);
        Route::delete('admin/country-requirements/{id}',        [CountryRequirementController::class, 'destroy']);
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

    // -------------------------------------------------------------------------
    // Owner Admin API — только superadmin
    // -------------------------------------------------------------------------

    Route::middleware(['auth:api', 'role:superadmin'])->prefix('owner')->group(function () {
        // Дашборд
        Route::get('dashboard', [OwnerController::class, 'dashboard']);

        // Агентства
        Route::get('agencies',          [OwnerController::class, 'agencies']);
        Route::get('agencies/{id}',     [OwnerController::class, 'agencyShow']);
        Route::patch('agencies/{id}',   [OwnerController::class, 'agencyUpdate']);
        Route::delete('agencies/{id}',  [OwnerController::class, 'agencyDestroy']);

        // Пользователи публичного портала
        Route::get('public-users',             [OwnerController::class, 'publicUsers']);
        Route::get('public-users/{id}',        [OwnerController::class, 'publicUserShow']);
        Route::post('public-users/{id}/block', [OwnerController::class, 'publicUserBlock']);

        // Лиды
        Route::get('leads',         [OwnerController::class, 'leads']);
        Route::patch('leads/{id}',  [OwnerController::class, 'leadUpdate']);

        // Страны (portal scoring)
        Route::get('countries',            [OwnerController::class, 'countries']);
        Route::post('countries',           [OwnerController::class, 'countryStore']);
        Route::patch('countries/{code}',   [OwnerController::class, 'countryUpdate']);

        // Типы виз
        Route::get('visa-types',           [OwnerController::class, 'visaTypes']);
        Route::post('visa-types',          [OwnerController::class, 'visaTypeStore']);
        Route::patch('visa-types/{slug}',  [OwnerController::class, 'visaTypeUpdate']);
        Route::delete('visa-types/{slug}', [OwnerController::class, 'visaTypeDestroy']);

        // Документы
        Route::get('documents', [OwnerController::class, 'documents']);

        // CRM-пользователи
        Route::get('crm-users',           [OwnerController::class, 'crmUsers']);
        Route::post('crm-users',          [OwnerController::class, 'crmUserStore']);
        Route::patch('crm-users/{id}',    [OwnerController::class, 'crmUserUpdate']);

        // Финансы
        Route::get('transactions', [OwnerController::class, 'transactions']);

        // Глобальный каталог услуг (только superadmin)
        Route::get('services',             [ServiceCatalogController::class, 'index']);
        Route::post('services',            [ServiceCatalogController::class, 'storeGlobal']);
        Route::patch('services/{id}',      [ServiceCatalogController::class, 'updateGlobal']);
        Route::delete('services/{id}',     [ServiceCatalogController::class, 'destroyGlobal']);
    });

    // -------------------------------------------------------------------------
    // Telegram Bot Webhook
    // -------------------------------------------------------------------------

    Route::post('telegram/webhook', [TelegramBotController::class, 'webhook']);

    // -------------------------------------------------------------------------
    // Публичный портал (лендинг visabor.uz)
    // -------------------------------------------------------------------------

    // Без авторизации
    Route::prefix('public')->group(function () {
        // Список стран для лендинга
        Route::get('countries', [PublicScoringController::class, 'countries']);

        // Phone auth
        Route::post('auth/send-otp',  [PublicAuthController::class, 'sendOtp'])->middleware('throttle:5,1');
        Route::post('auth/verify-otp',[PublicAuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
        Route::post('auth/login',     [PublicAuthController::class, 'loginWithPin'])->middleware('throttle:10,1');
    });

    // С токеном публичного пользователя
    Route::prefix('public')->middleware('auth.public')->group(function () {
        Route::post('auth/set-pin', [PublicAuthController::class, 'setPin']);

        Route::get('me',             [PublicProfileController::class, 'me']);
        Route::patch('me',           [PublicProfileController::class, 'update']);
        Route::post('me/passport',   [PublicProfileController::class, 'uploadPassport']);

        Route::get('scoring',        [PublicScoringController::class, 'scoreAll']);
        Route::get('scoring/{cc}',   [PublicScoringController::class, 'scoreCountry']);
    });

});
