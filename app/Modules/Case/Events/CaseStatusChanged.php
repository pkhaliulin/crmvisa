<?php

namespace App\Modules\Case\Events;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly VisaCase $case,
        public readonly string $previousStage,
        public readonly string $newStage,
        public readonly string $changedBy,
    ) {}
}
