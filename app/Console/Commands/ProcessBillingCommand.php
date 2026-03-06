<?php

namespace App\Console\Commands;

use App\Modules\Payment\Services\BillingEngine;
use Illuminate\Console\Command;

class ProcessBillingCommand extends Command
{
    protected $signature = 'billing:process';
    protected $description = 'Обработка просроченных подписок, dunning, авто-продление';

    public function handle(BillingEngine $engine): int
    {
        $count = $engine->processExpiredSubscriptions();
        $this->info("Обработано подписок: {$count}");

        return self::SUCCESS;
    }
}
