<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/crm.php';
    require __DIR__.'/api/owner.php';
    require __DIR__.'/api/public.php';
});
