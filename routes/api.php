<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Health endpoint (без авторизации, для UptimeRobot/Pingdom)
Route::get('health', HealthController::class);

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/crm.php';
    require __DIR__.'/api/owner.php';
    require __DIR__.'/api/public.php';
});
