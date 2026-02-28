<?php

namespace App\Console\Commands;

use App\Modules\Notification\Notifications\SlaWarningNotification;
use App\Modules\Workflow\Services\SlaService;
use Illuminate\Console\Command;

class SlaCheckCommand extends Command
{
    protected $signature   = 'visabor:sla-check';
    protected $description = 'Send SLA warning notifications for cases approaching deadline';

    public function handle(SlaService $slaService): int
    {
        $cases = $slaService->findCasesApproachingDeadline();

        if ($cases->isEmpty()) {
            $this->info('No cases approaching deadline.');
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($cases as $case) {
            // Уведомляем назначенного менеджера или владельца
            $recipient = $case->assignee ?? $case->agency->owner();

            if (! $recipient) {
                continue;
            }

            $recipient->notify(new SlaWarningNotification($case));
            $sent++;
        }

        $this->info("SLA warnings sent: {$sent}");

        return self::SUCCESS;
    }
}
