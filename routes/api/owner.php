<?php

use App\Modules\Document\Controllers\CountryRequirementController;
use App\Modules\Document\Controllers\DocumentTemplateController;
use App\Modules\Owner\Controllers\CountryDetailController;
use App\Modules\Owner\Controllers\FeatureFlagController;
use App\Modules\Owner\Controllers\MemoryController;
use App\Modules\Owner\Controllers\MonitoringController;
use App\Modules\Owner\Controllers\OwnerBillingController;
use App\Modules\Owner\Controllers\OwnerController;
use App\Modules\Owner\Controllers\WebsiteSettingsController;
use App\Modules\Payment\Controllers\BillingController;
use App\Modules\Payment\Controllers\MarketplaceController;
use App\Modules\Service\Controllers\ServiceCatalogController;
use Illuminate\Support\Facades\Route;

// Суперадмин: ручная активация плана + управление справочниками документов
Route::middleware(['auth:api', 'role:superadmin'])->group(function () {
    Route::post('admin/billing/activate',      [BillingController::class, 'adminActivate']);
    Route::get('admin/marketplace/stats',      [MarketplaceController::class, 'adminStats']);

    // Справочник шаблонов документов
    Route::get('admin/document-templates',                      [DocumentTemplateController::class, 'index']);
    Route::post('admin/document-templates',                     [DocumentTemplateController::class, 'store']);
    Route::get('admin/document-templates/{id}',                 [DocumentTemplateController::class, 'show']);
    Route::patch('admin/document-templates/{id}',               [DocumentTemplateController::class, 'update']);
    Route::patch('admin/document-templates/{id}/toggle-ai',     [DocumentTemplateController::class, 'toggleAi']);
    Route::delete('admin/document-templates/{id}',              [DocumentTemplateController::class, 'destroy']);
    Route::get('admin/ai-providers',                            [DocumentTemplateController::class, 'aiProviders']);
    Route::get('admin/ai-usage',                                [DocumentTemplateController::class, 'aiUsage']);

    // Требования стран к документам
    Route::get('admin/country-requirements',                [CountryRequirementController::class, 'index']);
    Route::get('admin/country-requirements/countries',      [CountryRequirementController::class, 'countries']);
    Route::post('admin/country-requirements',               [CountryRequirementController::class, 'store']);
    Route::patch('admin/country-requirements/{id}',         [CountryRequirementController::class, 'update']);
    Route::delete('admin/country-requirements/{id}',        [CountryRequirementController::class, 'destroy']);
});

// Owner Admin API -- только superadmin
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

    // Country Operations Center -- детальная страница страны
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

    // Биллинг: тарифы, купоны, настройки, дашборд
    Route::prefix('billing')->group(function () {
        Route::get('dashboard',               [OwnerBillingController::class, 'dashboard']);
        Route::get('plans',                   [OwnerBillingController::class, 'plans']);
        Route::post('plans',                  [OwnerBillingController::class, 'planStore']);
        Route::patch('plans/{slug}',          [OwnerBillingController::class, 'planUpdate']);
        Route::delete('plans/{slug}',         [OwnerBillingController::class, 'planDestroy']);
        Route::get('settings',                [OwnerBillingController::class, 'settings']);
        Route::patch('settings',              [OwnerBillingController::class, 'updateSettings']);
        Route::get('coupons',                 [OwnerBillingController::class, 'coupons']);
        Route::post('coupons',                [OwnerBillingController::class, 'couponStore']);
        Route::patch('coupons/{id}',          [OwnerBillingController::class, 'couponUpdate']);
        Route::delete('coupons/{id}',         [OwnerBillingController::class, 'couponDestroy']);
        Route::get('addons',                  [OwnerBillingController::class, 'addons']);
        Route::post('addons',                 [OwnerBillingController::class, 'addonStore']);
        Route::patch('addons/{id}',           [OwnerBillingController::class, 'addonUpdate']);
        Route::delete('addons/{id}',          [OwnerBillingController::class, 'addonDestroy']);
        Route::get('invoices',                [OwnerBillingController::class, 'invoices']);
        Route::post('activate',               [OwnerBillingController::class, 'activateSubscription']);
    });

    // Feature Flags
    Route::get('feature-flags',          [FeatureFlagController::class, 'index']);
    Route::post('feature-flags',         [FeatureFlagController::class, 'store']);
    Route::patch('feature-flags/{id}',   [FeatureFlagController::class, 'update']);
    Route::delete('feature-flags/{id}',  [FeatureFlagController::class, 'destroy']);

    // Каналы лидогенерации
    Route::get('lead-channels',              [\App\Modules\LeadGen\Controllers\LeadChannelAdminController::class, 'index']);
    Route::get('lead-channels/{id}',         [\App\Modules\LeadGen\Controllers\LeadChannelAdminController::class, 'show']);
    Route::post('lead-channels',             [\App\Modules\LeadGen\Controllers\LeadChannelAdminController::class, 'store']);
    Route::patch('lead-channels/{id}',       [\App\Modules\LeadGen\Controllers\LeadChannelAdminController::class, 'update']);
    Route::post('lead-channels/{id}/toggle', [\App\Modules\LeadGen\Controllers\LeadChannelAdminController::class, 'toggle']);
    Route::delete('lead-channels/{id}',      [\App\Modules\LeadGen\Controllers\LeadChannelAdminController::class, 'destroy']);

    // Память проекта (документация)
    Route::get('memory', [MemoryController::class, 'index']);

    // Настройки сайта VisaBor
    Route::get('website-settings', [WebsiteSettingsController::class, 'index']);
    Route::put('website-settings', [WebsiteSettingsController::class, 'update']);
    Route::post('website-settings/clear-cache', [WebsiteSettingsController::class, 'clearCache']);

    // База знаний (глобальная)
    Route::prefix('knowledge')->group(function () {
        Route::get('/',             [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'index']);
        Route::get('/stats',        [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'stats']);
        Route::get('/pending',      [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'pendingNotes']);
        Route::get('/{id}',         [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'show']);
        Route::post('/',            [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'store']);
        Route::patch('/{id}',       [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'update']);
        Route::delete('/{id}',      [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'destroy']);
        Route::post('/{id}/publish', [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'publish']);
        Route::post('/notes/{id}/moderate', [\App\Modules\Knowledge\Controllers\OwnerKnowledgeController::class, 'moderateNote']);
    });

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
