<?php

use App\Modules\Group\Controllers\PublicGroupController;
use App\Modules\Agency\Controllers\AgencySettingsController;
use App\Modules\Agency\Controllers\ReportController;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Controllers\ClientAuthController;
use App\Modules\Case\Controllers\CaseController;
use App\Modules\Case\Controllers\DashboardController;
use App\Modules\Case\Controllers\KanbanController;
use App\Modules\Client\Controllers\ClientController;
use App\Modules\Client\Controllers\ClientPortalController;
use App\Modules\PublicPortal\Controllers\PublicAgencyController;
use App\Modules\PublicPortal\Controllers\PublicAuthController;
use App\Modules\PublicPortal\Controllers\PublicFamilyController;
use App\Modules\PublicPortal\Controllers\PublicProfileController;
use App\Modules\PublicPortal\Controllers\PublicReviewController;
use App\Modules\PublicPortal\Controllers\PublicScoringController;
use App\Modules\Document\Controllers\ChecklistController;
use App\Modules\Document\Controllers\CountryRequirementController;
use App\Modules\Document\Controllers\DocumentController;
use App\Modules\Document\Controllers\DocumentTemplateController;
use App\Modules\Payment\Controllers\BillingController;
use App\Modules\Payment\Controllers\ClientPaymentController;
use App\Modules\Payment\Controllers\MarketplaceController;
use App\Modules\Scoring\Controllers\ScoringController;
use App\Modules\Service\Controllers\ServiceCatalogController;
use App\Modules\Owner\Controllers\CountryDetailController;
use App\Modules\Owner\Controllers\FeatureFlagController;
use App\Modules\Owner\Controllers\MemoryController;
use App\Modules\Owner\Controllers\MonitoringController;
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
        Route::post('clients/parse-passport', [ClientController::class, 'parsePassport']);
        Route::apiResource('clients', ClientController::class)->middleware([
            'plan.limit:max_cases', // enforce на создание — POST /clients
        ]);

        // Канбан
        Route::get('kanban', [KanbanController::class, 'board']);

        // Дашборд
        Route::get('dashboard',                        [DashboardController::class, 'index']);
        Route::get('dashboard/overdue',                [DashboardController::class, 'overdue']);
        Route::get('dashboard/managers/{id}/cases',    [DashboardController::class, 'managerCases']);

        // Заявки
        Route::get('cases/critical',          [CaseController::class, 'critical']);
        Route::post('cases/{id}/move-stage',        [CaseController::class, 'moveStage']);
        Route::post('cases/{id}/complete',          [CaseController::class, 'complete']);
        Route::post('cases/{id}/submit-to-embassy', [CaseController::class, 'submitToEmbassy']);
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

        // Пакеты услуг агентства
        Route::get('agency/packages',          [ServiceCatalogController::class, 'myPackages']);
        Route::post('agency/packages',         [ServiceCatalogController::class, 'store']);
        Route::patch('agency/packages/{id}',   [ServiceCatalogController::class, 'update']);
        Route::delete('agency/packages/{id}',  [ServiceCatalogController::class, 'destroy']);

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

        // Country Operations Center — детальная страница страны
        Route::get('countries/{code}/detail',                  [CountryDetailController::class, 'countryShow']);
        Route::patch('countries/{code}/embassy',               [CountryDetailController::class, 'updateEmbassy']);
        Route::patch('countries/{code}/submission',            [CountryDetailController::class, 'updateSubmission']);
        Route::patch('countries/{code}/visa-center',           [CountryDetailController::class, 'updateVisaCenter']);
        Route::patch('countries/{code}/finance',               [CountryDetailController::class, 'updateFinance']);
        Route::get('countries/{code}/visa-settings',           [CountryDetailController::class, 'visaSettings']);
        Route::post('countries/{code}/visa-settings',          [CountryDetailController::class, 'visaSettingStore']);
        Route::patch('countries/{code}/visa-settings/{id}',    [CountryDetailController::class, 'visaSettingUpdate']);
        Route::delete('countries/{code}/visa-settings/{id}',   [CountryDetailController::class, 'visaSettingDestroy']);
        Route::get('countries/{code}/requirements',            [CountryDetailController::class, 'requirements']);
        Route::get('countries/{code}/scoring',                 [CountryDetailController::class, 'scoring']);
        Route::patch('countries/{code}/scoring',               [CountryDetailController::class, 'updateScoring']);
        Route::get('countries/{code}/stats',                   [CountryDetailController::class, 'stats']);

        // Типы виз
        Route::get('visa-types',           [OwnerController::class, 'visaTypes']);
        Route::post('visa-types',          [OwnerController::class, 'visaTypeStore']);
        Route::patch('visa-types/{slug}',  [OwnerController::class, 'visaTypeUpdate']);
        Route::delete('visa-types/{slug}', [OwnerController::class, 'visaTypeDestroy']);

        // Справочники
        Route::get('references',                          [\App\Modules\Owner\Controllers\ReferenceController::class, 'categories']);
        Route::get('references/all',                      [\App\Modules\Owner\Controllers\ReferenceController::class, 'all']);
        Route::get('references/{category}',               [\App\Modules\Owner\Controllers\ReferenceController::class, 'index']);
        Route::post('references/{category}',              [\App\Modules\Owner\Controllers\ReferenceController::class, 'store']);
        Route::patch('references/{category}/{id}',        [\App\Modules\Owner\Controllers\ReferenceController::class, 'update']);
        Route::delete('references/{category}/{id}',       [\App\Modules\Owner\Controllers\ReferenceController::class, 'destroy']);

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

        // Feature Flags
        Route::get('feature-flags',          [FeatureFlagController::class, 'index']);
        Route::post('feature-flags',         [FeatureFlagController::class, 'store']);
        Route::patch('feature-flags/{id}',   [FeatureFlagController::class, 'update']);
        Route::delete('feature-flags/{id}',  [FeatureFlagController::class, 'destroy']);

        // Память проекта (документация)
        Route::get('memory', [MemoryController::class, 'index']);

        // Мониторинг системы
        Route::prefix('monitoring')->group(function () {
            Route::get('health',   [MonitoringController::class, 'health']);
            Route::get('errors',   [MonitoringController::class, 'errors']);
            Route::get('activity', [MonitoringController::class, 'activity']);
            Route::get('metrics',  [MonitoringController::class, 'metrics']);
            Route::get('alerts',   [MonitoringController::class, 'alerts']);
            Route::get('queue',    [MonitoringController::class, 'queue']);
            Route::get('sentry',   [MonitoringController::class, 'sentry']);
            Route::post('queue/{id}/retry',  [MonitoringController::class, 'retryJob']);
            Route::delete('queue/{id}',      [MonitoringController::class, 'deleteJob']);
        });
    });

    // -------------------------------------------------------------------------
    // Telegram Bot Webhook
    // -------------------------------------------------------------------------

    Route::post('telegram/webhook', [TelegramBotController::class, 'webhook']);

    // -------------------------------------------------------------------------
    // Публичный портал (лендинг visabor.uz)
    // -------------------------------------------------------------------------

    // Без авторизации
    Route::prefix('public')->middleware('locale')->group(function () {
        // Справочники (публичные, без авторизации)
        Route::get('references', [\App\Modules\Owner\Controllers\ReferenceController::class, 'all']);

        // Список стран для лендинга
        Route::get('countries', [PublicScoringController::class, 'countries']);
        Route::get('countries/{code}', [PublicScoringController::class, 'countryView']);
        Route::get('served-countries', [PublicScoringController::class, 'servedCountries']);

        // Phone auth
        Route::post('auth/send-otp',  [PublicAuthController::class, 'sendOtp'])->middleware('throttle:5,1');
        Route::post('auth/verify-otp',[PublicAuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
        Route::post('auth/login',     [PublicAuthController::class, 'loginWithPin'])->middleware('throttle:10,1');
    });

    // С токеном публичного пользователя
    Route::prefix('public')->middleware(['locale', 'auth.public'])->group(function () {
        Route::post('auth/set-pin', [PublicAuthController::class, 'setPin']);

        Route::get('me',             [PublicProfileController::class, 'me']);
        Route::patch('me',           [PublicProfileController::class, 'update']);
        Route::post('me/passport',   [PublicProfileController::class, 'uploadPassport']);
        Route::post('me/change-phone/send-otp', [PublicProfileController::class, 'changePhoneSendOtp']);
        Route::post('me/change-phone/verify',   [PublicProfileController::class, 'changePhoneVerify']);
        Route::get('me/cases',                                    [PublicProfileController::class, 'cases']);
        Route::post('me/cases',                                   [PublicProfileController::class, 'createDraftCase']);
        Route::get('me/cases/{id}',                               [PublicProfileController::class, 'caseDetail']);
        Route::patch('me/cases/{id}',                             [PublicProfileController::class, 'updateCase']);
        Route::post('me/cases/{caseId}/checklist/{itemId}/upload',[PublicProfileController::class, 'uploadChecklistItem']);

        // Семья — профиль
        Route::get('me/family',                [PublicFamilyController::class, 'index']);
        Route::post('me/family',               [PublicFamilyController::class, 'store']);
        Route::patch('me/family/{id}',         [PublicFamilyController::class, 'update']);
        Route::delete('me/family/{id}',        [PublicFamilyController::class, 'destroy']);

        // Семья — привязка к заявке
        Route::get('me/cases/{id}/family',     [PublicFamilyController::class, 'caseMembers']);
        Route::post('me/cases/{id}/family',    [PublicFamilyController::class, 'attachToCase']);
        Route::delete('me/cases/{id}/family/{fid}', [PublicFamilyController::class, 'detachFromCase']);

        Route::get('agencies',              [PublicAgencyController::class, 'index']);
        Route::get('agencies/{id}',         [PublicAgencyController::class, 'show']);
        Route::post('leads',                [PublicAgencyController::class, 'submitLead']);
        Route::get('agencies/{id}/reviews', [PublicReviewController::class, 'index']);
        Route::post('agencies/{id}/reviews',[PublicReviewController::class, 'store']);
        Route::get('me/can-review/{id}',    [PublicReviewController::class, 'canReview']);

        // Inline-выбор агентства для кейса + смена агентства
        Route::get('me/cases/{id}/agencies',       [PublicProfileController::class, 'caseAgencies']);
        Route::post('me/cases/{id}/change-agency',  [PublicProfileController::class, 'changeAgency']);

        // Группы
        Route::get('me/groups',                     [PublicGroupController::class, 'index']);
        Route::post('me/groups',                    [PublicGroupController::class, 'store']);
        Route::get('me/groups/{id}',                [PublicGroupController::class, 'show']);
        Route::post('me/groups/{id}/members',       [PublicGroupController::class, 'addMember']);
        Route::delete('me/groups/{id}/members/{mid}', [PublicGroupController::class, 'removeMember']);
        Route::post('me/groups/{id}/agency',        [PublicGroupController::class, 'setAgency']);
        Route::get('me/groups/{id}/agencies',       [PublicGroupController::class, 'agencies']);
        Route::get('me/groups/{id}/members/{memberId}/case', [PublicGroupController::class, 'memberCaseDetail']);
        Route::post('me/groups/{id}/pay',           [PublicGroupController::class, 'payForGroup']);

        // Оплата клиента
        Route::post('me/payments/initiate',        [ClientPaymentController::class, 'initiate']);
        Route::post('me/payments/mark-paid',       [ClientPaymentController::class, 'markAsPaid']);
        Route::get('me/cases/{id}/payment',        [ClientPaymentController::class, 'status']);
        Route::get('me/billing',                   [ClientPaymentController::class, 'history']);

        Route::get('scoring',        [PublicScoringController::class, 'scoreAll']);
        Route::get('scoring/{cc}',   [PublicScoringController::class, 'scoreCountry']);
    });

    // Webhook от платёжных систем (без авторизации!)
    Route::post('public/payments/callback/{provider}', [ClientPaymentController::class, 'callback']);

});
