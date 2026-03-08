<?php

namespace App\Modules\Client\Events;

use App\Modules\Client\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientCreatedViaPortal
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Client $client,
        public readonly string $agencyId,
        public readonly ?string $leadId,
    ) {}
}
