<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceLead extends BaseModel
{
    use \App\Support\Traits\HasUuid;

    protected $table = 'marketplace_leads';

    protected $fillable = [
        'agency_id',
        'client_name',
        'client_phone',
        'client_email',
        'country_code',
        'visa_type',
        'message',
        'status',
        'converted_case_id',
        'utm_source',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }
}
