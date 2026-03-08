<?php

namespace App\Modules\Agency\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyGoal extends BaseModel
{
    use HasTenant;

    protected $table = 'agency_goals';

    protected $fillable = [
        'agency_id',
        'year',
        'month',
        'target_clients',
        'target_revenue',
        'target_cases',
        'created_by',
    ];

    protected $casts = [
        'year'           => 'integer',
        'month'          => 'integer',
        'target_clients' => 'integer',
        'target_revenue' => 'integer',
        'target_cases'   => 'integer',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'created_by');
    }
}
