<?php

namespace App\Console\Commands;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PaymentTimeoutCommand extends Command
{
    protected $signature = 'visabor:payment-timeout';

    protected $description = 'Вернуть просроченные неоплаченные заявки из awaiting_payment в draft';

    public function handle(): int
    {
        $cases = VisaCase::where('public_status', 'awaiting_payment')
            ->where(function ($q) {
                $q->whereNull('payment_status')
                  ->orWhereIn('payment_status', ['unpaid', 'pending']);
            })
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->get();

        $count = 0;

        foreach ($cases as $case) {
            $case->update([
                'public_status'  => 'draft',
                'agency_id'      => null,
                'payment_status' => 'unpaid',
            ]);

            activity()
                ->performedOn($case)
                ->log('Автовозврат в черновик: оплата не получена за 24 часа');

            $count++;
        }

        $this->info("Возвращено в черновик: {$count}");

        return self::SUCCESS;
    }
}
