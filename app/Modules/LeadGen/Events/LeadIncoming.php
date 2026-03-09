<?php

namespace App\Modules\LeadGen\Events;

use App\Modules\Case\Models\VisaCase;
use App\Modules\Client\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadIncoming
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly VisaCase $case,
        public readonly Client $client,
        public readonly string $agencyId,
        public readonly ?string $source = null,
        public readonly ?string $channelCode = null,
    ) {}
}
