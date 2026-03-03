<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneRequestLogsCommand extends Command
{
    protected $signature = 'monitoring:prune {--days=30 : Удалять записи старше N дней}';
    protected $description = 'Удаление старых записей из request_logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $deleted = DB::table('request_logs')
                ->where('created_at', '<', $cutoff)
                ->limit(5000)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        $this->info("Удалено {$total} записей старше {$days} дней.");

        return self::SUCCESS;
    }
}
