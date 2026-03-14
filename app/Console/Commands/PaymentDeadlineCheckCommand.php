<?php

namespace App\Console\Commands;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Case\Services\CaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PaymentDeadlineCheckCommand extends Command
{
    protected $signature = 'visabor:payment-deadline-check';
    protected $description = 'Автоблокировка заявок с просроченным дедлайном доплаты';

    public function handle(): int
    {
        DB::statement("SET app.is_superadmin = 'true'");

        $cases = VisaCase::where('payment_status', 'prepayment')
            ->where('payment_blocked', false)
            ->whereNotNull('payment_deadline')
            ->where('payment_deadline', '<', now()->toDateString())
            ->whereNull('deleted_at')
            ->get();

        $count = 0;
        foreach ($cases as $case) {
            $case->update(['payment_blocked' => true]);

            CaseService::logActivity(
                $case,
                'payment_blocked',
                'Заявка автоматически заблокирована: просрочен дедлайн доплаты (' . $case->payment_deadline->toDateString() . ')',
                ['deadline' => $case->payment_deadline->toDateString()],
                true,
            );

            $count++;
        }

        $this->info("Заблокировано заявок: {$count}");

        return self::SUCCESS;
    }
}
