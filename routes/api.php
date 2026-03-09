<?php

use App\Http\Controllers\HealthController;
use App\Modules\Document\Controllers\DocumentDownloadController;
use Illuminate\Support\Facades\Route;

// Health endpoint (без авторизации, для UptimeRobot/Pingdom)
Route::get('health', HealthController::class);

// Signed URL для скачивания документов (без авторизации — подпись в URL)
Route::get('documents/{document}/download', [DocumentDownloadController::class, 'download'])
    ->name('documents.download');

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/crm.php';
    require __DIR__.'/api/owner.php';
    require __DIR__.'/api/public.php';

    // Приём лидов по API-ключу агентства (без JWT, аутентификация по vbk_ токену)
    Route::middleware(['auth.apikey', 'throttle:60,1'])
        ->post('leads/incoming', [\App\Modules\LeadGen\Controllers\IncomingLeadController::class, 'store']);
});
