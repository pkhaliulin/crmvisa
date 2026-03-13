<?php

use App\Http\Controllers\HealthController;
use App\Modules\Document\Controllers\DocumentDownloadController;
use Illuminate\Support\Facades\Route;

// Health endpoint (без авторизации, для UptimeRobot/Pingdom)
Route::get('health', HealthController::class);

// Signed URL для документов (без авторизации — подпись в URL)
Route::get('documents/{document}/download', [DocumentDownloadController::class, 'download'])
    ->name('documents.download');
Route::get('documents/{document}/preview', [DocumentDownloadController::class, 'preview'])
    ->name('documents.preview');
Route::get('documents/{document}/thumbnail', [DocumentDownloadController::class, 'thumbnail'])
    ->name('documents.thumbnail');

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/crm.php';
    require __DIR__.'/api/owner.php';
    require __DIR__.'/api/public.php';

    // Приём лидов по API-ключу агентства (без JWT, аутентификация по vbk_ токену)
    Route::middleware(['auth.apikey', 'throttle:leads_per_agency'])
        ->post('leads/incoming', [\App\Modules\LeadGen\Controllers\IncomingLeadController::class, 'store']);

    // Webhook-и от платёжных систем для подписок (без авторизации, проверка подписи внутри)
    Route::post('payments/click/callback', [\App\Modules\Payment\Controllers\PaymentController::class, 'clickCallback']);
    Route::post('payments/payme/callback', [\App\Modules\Payment\Controllers\PaymentController::class, 'paymeCallback']);
    Route::post('payments/uzum/callback', [\App\Modules\Payment\Controllers\PaymentController::class, 'uzumCallback']);
});
