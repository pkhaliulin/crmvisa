<?php

namespace App\Console\Commands;

use App\Modules\Case\Models\CaseStage;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PaymentTimeoutCommand extends Command
{
    protected $signature = 'visabor:payment-timeout';

    protected $description = 'Вернуть просроченные неоплаченные заявки из awaiting_payment в qualification';

    public function handle(CaseService $caseService): int
    {
        $cases = VisaCase::where('stage', 'awaiting_payment')
            ->where(function ($q) {
                $q->whereNull('payment_status')
                  ->orWhereIn('payment_status', ['unpaid', 'pending']);
            })
            ->whereHas('stageHistory', function ($q) {
                $q->whereNull('exited_at')
                  ->where('stage', 'awaiting_payment')
                  ->whereNotNull('sla_due_at')
                  ->where('sla_due_at', '<', Carbon::now());
            })
            ->get();

        $count = 0;

        foreach ($cases as $case) {
            // Пометить текущий этап как просроченный
            CaseStage::where('case_id', $case->id)
                ->whereNull('exited_at')
                ->where('stage', 'awaiting_payment')
                ->update(['is_overdue' => true]);

            $caseService->moveToStageSystem($case, 'qualification', 'Автовозврат: оплата не получена в срок');
            $count++;
        }

        $this->info("Возвращено в квалификацию: {$count}");

        return self::SUCCESS;
    }
}
