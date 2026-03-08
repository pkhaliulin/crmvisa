<?php

namespace App\Modules\Case\Events;

use App\Modules\Case\Models\VisaCase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaViolation
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly VisaCase $case,
        public readonly string $stage,
        public readonly \DateTimeInterface $dueAt,
        public readonly int $overdueDays,
    ) {}
}
