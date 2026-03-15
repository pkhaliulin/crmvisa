<?php

namespace App\Modules\Scoring\Jobs;

use App\Modules\Scoring\Models\ClientProfile;
use App\Modules\Scoring\Models\ClientScore;
use App\Modules\Scoring\Services\ScoringDataAdapter;
use App\Modules\Scoring\Services\UnifiedScoringEngine;
use App\Support\Traits\HasTenantJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CalculateClientScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasTenantJob;

    public int $tries = 3;

    public function __construct(public ClientProfile $profile)
    {
        // Получить agency_id через client -> agency_id
        $agencyId = DB::table('clients')
            ->where('id', $profile->client_id)
            ->value('agency_id');
        $this->captureTenant($agencyId);
    }

    public function handle(UnifiedScoringEngine $engine): void
    {
        $this->setTenantContext();
        $data = ScoringDataAdapter::fromClientProfile($this->profile);

        $countries = DB::table('scoring_country_weights')
            ->distinct()
            ->pluck('country_code');

        if ($countries->isEmpty()) {
            $countries = collect(['DE', 'ES', 'FR', 'IT', 'PL', 'CZ', 'GB', 'US', 'CA', 'KR', 'AE']);
        }

        foreach ($countries as $cc) {
            $result = $engine->scoreForCountry($data, $cc);

            ClientScore::updateOrCreate(
                ['client_id' => $this->profile->client_id, 'country_code' => $cc],
                [
                    'score'           => $result['score'],
                    'block_scores'    => $result['blocks'],
                    'flags'           => $result['flags'],
                    'recommendations' => $result['recommendations'],
                    'weak_blocks'     => collect($result['blocks'])->filter(fn ($v) => $v < 50)->keys()->toArray(),
                    'is_blocked'      => $result['is_blocked'],
                    'calculated_at'   => now(),
                ]
            );
        }
    }
}
