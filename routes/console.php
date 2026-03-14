<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('visabor:sla-check')->everyFifteenMinutes();
Schedule::command('visabor:payment-timeout')->everyFifteenMinutes();
Schedule::command('monitoring:prune')->dailyAt('03:00');
Schedule::call(function () {
    app(\App\Modules\Payment\Services\BillingEngine::class)->applyPendingDowngrades();
})->dailyAt('00:05')->name('billing:apply-pending-downgrades');

Schedule::call(function () {
    app(\App\Modules\Payment\Services\BillingEngine::class)->processExpiredSubscriptions();
})->dailyAt('01:00')->name('billing:process-expired');

Schedule::job(new \App\Modules\Case\Jobs\SendSlaWarningsJob)->everyFifteenMinutes();

Schedule::command('app:verify-backup')->dailyAt('04:00');
Schedule::command('tasks:generate-recurring')->dailyAt('06:00');
Schedule::command('visabor:payment-deadline-check')->dailyAt('09:00');
