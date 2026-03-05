<?php

namespace App\Console\Commands;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Payment\Models\ClientPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PaymentTimeoutCommand extends Command
{
    protected $signature = 'visabor:payment-timeout';

    protected $description = 'Аннулировать просроченные счета (expires_at) и вернуть заявки в awaiting_payment';

    public function handle(): int
    {
        // Аннулировать платежи с истекшим сроком
        $expired = ClientPayment::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;

        foreach ($expired as $payment) {
            DB::transaction(function () use ($payment) {
                if ($payment->agency_id) {
                    DB::statement("SET LOCAL app.current_tenant_id = '{$payment->agency_id}'");
                }
                DB::statement("SET LOCAL app.is_superadmin = 'true'");

                $payment->update([
                    'status'   => 'expired',
                    'metadata' => array_merge($payment->metadata ?? [], [
                        'expired_reason' => 'timeout',
                        'expired_at'     => now()->toDateTimeString(),
                    ]),
                ]);

                // Вернуть заявку в состояние awaiting_payment (можно повторно оплатить)
                if ($payment->case_id) {
                    $case = VisaCase::find($payment->case_id);
                    if ($case && $case->payment_status !== 'paid') {
                        $case->update(['payment_status' => 'unpaid']);
                    }
                }
            });

            $count++;
        }

        if ($count > 0) {
            $this->info("Аннулировано счетов: {$count}");
        }

        return self::SUCCESS;
    }
}
