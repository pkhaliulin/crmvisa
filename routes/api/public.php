<?php

use App\Modules\Group\Controllers\PublicGroupController;
use App\Modules\Payment\Controllers\ClientPaymentController;
use App\Modules\Payment\Controllers\MarketplaceController;
use App\Modules\PublicPortal\Controllers\PublicAgencyController;
use App\Modules\PublicPortal\Controllers\PublicAuthController;
use App\Modules\PublicPortal\Controllers\PublicFamilyController;
use App\Modules\PublicPortal\Controllers\PublicProfileController;
use App\Modules\PublicPortal\Controllers\PublicReviewController;
use App\Modules\PublicPortal\Controllers\PublicScoringController;
use App\Modules\PublicPortal\Controllers\RecoveryController;
use App\Modules\TelegramBot\Controllers\TelegramBotController;
use Illuminate\Support\Facades\Route;

// Маркетплейс -- публичный (без авторизации)
Route::get('marketplace',             [MarketplaceController::class, 'index']);
Route::get('marketplace/{slug}',      [MarketplaceController::class, 'show']);
Route::post('marketplace/{slug}/lead', [MarketplaceController::class, 'sendLead'])->middleware('throttle:5,1');

// Telegram Bot Webhook
Route::post('telegram/webhook', [TelegramBotController::class, 'webhook']);

// Публичный портал -- без авторизации
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

    // Recovery (без авторизации)
    Route::post('recovery/request',      [RecoveryController::class, 'request'])->middleware('throttle:3,1');
    Route::post('recovery/verify-token', [RecoveryController::class, 'verifyToken']);
    Route::post('recovery/send-otp',     [RecoveryController::class, 'sendOtp'])->middleware('throttle:5,1');
    Route::post('recovery/confirm',      [RecoveryController::class, 'confirm'])->middleware('throttle:5,1');
});

// Публичный портал -- с токеном публичного пользователя
Route::prefix('public')->middleware(['locale', 'auth.public'])->group(function () {
    Route::post('auth/set-pin', [PublicAuthController::class, 'setPin']);

    Route::get('me',             [PublicProfileController::class, 'me']);
    Route::patch('me',           [PublicProfileController::class, 'update']);
    Route::post('me/passport',   [PublicProfileController::class, 'uploadPassport']);
    Route::post('me/email',        [PublicProfileController::class, 'saveEmail']);
    Route::post('me/email/verify', [PublicProfileController::class, 'verifyEmail']);
    Route::post('me/change-phone/send-otp', [PublicProfileController::class, 'changePhoneSendOtp']);
    Route::post('me/change-phone/verify',   [PublicProfileController::class, 'changePhoneVerify']);
    Route::get('me/cases',                                    [PublicProfileController::class, 'cases']);
    Route::post('me/cases',                                   [PublicProfileController::class, 'createDraftCase'])->middleware('throttle:10,1');
    Route::get('me/cases/{id}',                               [PublicProfileController::class, 'caseDetail']);
    Route::patch('me/cases/{id}',                             [PublicProfileController::class, 'updateCase']);
    Route::post('me/cases/{id}/cancel',                      [PublicProfileController::class, 'cancelCase']);
    Route::post('me/cases/{caseId}/checklist/{itemId}/upload',[PublicProfileController::class, 'uploadChecklistItem'])->middleware('throttle:20,1');

    // Семья -- профиль
    Route::get('me/family',                [PublicFamilyController::class, 'index']);
    Route::post('me/family',               [PublicFamilyController::class, 'store'])->middleware('throttle:10,1');
    Route::patch('me/family/{id}',         [PublicFamilyController::class, 'update']);
    Route::delete('me/family/{id}',        [PublicFamilyController::class, 'destroy']);

    // Семья -- привязка к заявке
    Route::get('me/cases/{id}/family',     [PublicFamilyController::class, 'caseMembers']);
    Route::post('me/cases/{id}/family',    [PublicFamilyController::class, 'attachToCase']);
    Route::delete('me/cases/{id}/family/{fid}', [PublicFamilyController::class, 'detachFromCase']);

    Route::get('agencies',              [PublicAgencyController::class, 'index']);
    Route::get('agencies/{id}',         [PublicAgencyController::class, 'show']);
    Route::post('leads',                [PublicAgencyController::class, 'submitLead'])->middleware('throttle:10,1');
    Route::get('agencies/{id}/reviews', [PublicReviewController::class, 'index']);
    Route::post('agencies/{id}/reviews',[PublicReviewController::class, 'store'])->middleware('throttle:5,1');
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
    Route::post('me/payments/initiate',        [ClientPaymentController::class, 'initiate'])->middleware('throttle:5,1');
    Route::post('me/payments/mark-paid',       [ClientPaymentController::class, 'markAsPaid'])->middleware('throttle:3,1');
    Route::get('me/cases/{id}/payment',        [ClientPaymentController::class, 'status']);
    Route::get('me/billing',                   [ClientPaymentController::class, 'history']);

    Route::get('scoring',            [PublicScoringController::class, 'scoreAll'])->middleware('throttle:5,1');
    Route::get('scoring/profile',    [PublicScoringController::class, 'scoreProfile']);
    Route::post('scoring/batch',     [PublicScoringController::class, 'scoreBatch'])->middleware('throttle:10,1');
    Route::get('scoring/{cc}',       [PublicScoringController::class, 'scoreCountry']);
});

// Webhook от платёжных систем (без авторизации!)
Route::post('public/payments/callback/{provider}', [ClientPaymentController::class, 'callback']);
