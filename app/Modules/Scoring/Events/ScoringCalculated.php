<?php

namespace App\Modules\Scoring\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoringCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $clientId,
        public readonly string $countryCode,
        public readonly int $score,
        public readonly string $riskLevel,
        public readonly ?string $agencyId = null,
    ) {}
}
