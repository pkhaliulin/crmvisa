<?php

namespace App\Modules\Case\Jobs;

use App\Modules\Case\Events\SlaViolation;
use App\Modules\Case\Models\VisaCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSlaWarningsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $warningThreshold = now()->addDays(3);

        $cases = VisaCase::query()
            ->whereNotNull('critical_date')
            ->where('critical_date', '<=', $warningThreshold)
            ->whereNotIn('stage', ['completed', 'rejected', 'cancelled'])
            ->get();

        foreach ($cases as $case) {
            $overdueDays = (int) now()->diffInDays($case->critical_date, false);

            Log::channel('single')->warning('SLA Warning: case approaching critical date', [
                'case_id'       => $case->id,
                'agency_id'     => $case->agency_id,
                'stage'         => $case->stage,
                'critical_date' => $case->critical_date->toDateString(),
                'overdue_days'  => abs($overdueDays),
                'is_overdue'    => $overdueDays < 0,
            ]);

            if ($overdueDays < 0) {
                SlaViolation::dispatch(
                    $case,
                    $case->stage,
                    $case->critical_date,
                    abs($overdueDays),
                );
            }
        }

        Log::channel('single')->info('SendSlaWarningsJob completed', [
            'cases_checked' => $cases->count(),
        ]);
    }
}
