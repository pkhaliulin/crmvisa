<?php

namespace App\Modules\Document\Events;

use App\Modules\Document\Models\Document;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Document $document,
        public readonly string $uploadedBy,
        public readonly string $source, // 'crm' | 'portal'
    ) {}
}
