<?php

namespace App\Modules\Case\Repositories;

use App\Modules\Case\Models\VisaCase;
use App\Support\Abstracts\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class CaseRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new VisaCase());
    }

    public function byStage(string $stage): Collection
    {
        return VisaCase::where('stage', $stage)->with(['client', 'assignee'])->get();
    }

    public function critical(string $agencyId): Collection
    {
        return VisaCase::where('agency_id', $agencyId)
            ->whereNotNull('critical_date')
            ->whereDate('critical_date', '<=', now()->addDays(5))
            ->with(['client', 'assignee'])
            ->orderBy('critical_date')
            ->get();
    }
}
