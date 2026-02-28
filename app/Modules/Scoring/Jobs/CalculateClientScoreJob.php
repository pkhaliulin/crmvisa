<?php

namespace App\Modules\Scoring\Jobs;

use App\Modules\Scoring\Models\ClientProfile;
use App\Modules\Scoring\Services\ScoringEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateClientScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public ClientProfile $profile) {}

    public function handle(ScoringEngine $engine): void
    {
        $engine->calculateAll($this->profile);
    }
}
