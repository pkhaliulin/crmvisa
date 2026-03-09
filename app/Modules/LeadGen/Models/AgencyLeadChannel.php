<?php

namespace App\Modules\LeadGen\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;

class AgencyLeadChannel extends BaseModel
{
    use HasTenant;

    protected $table = 'agency_lead_channels';

    protected $fillable = [
        'agency_id',
        'channel_id',
        'is_active',
        'connected_at',
        'settings',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'connected_at' => 'datetime',
        'settings'     => 'array',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function channel()
    {
        return $this->belongsTo(LeadChannel::class, 'channel_id');
    }
}
