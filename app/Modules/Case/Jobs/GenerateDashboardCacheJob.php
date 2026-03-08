<?php

namespace App\Modules\Case\Jobs;

use App\Support\Services\AgencyCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateDashboardCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $agencyId,
    ) {}

    public function handle(): void
    {
        AgencyCacheService::invalidateAgency($this->agencyId);

        Log::channel('single')->info('GenerateDashboardCacheJob: cache invalidated and warming', [
            'agency_id' => $this->agencyId,
        ]);
    }
}
