<?php

namespace App\Modules\Agency\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyWorkCountry extends BaseModel
{
    use HasTenant;

    protected $table = 'agency_work_countries';

    protected $fillable = [
        'agency_id',
        'country_code',
        'visa_types',
        'is_active',
    ];

    protected $casts = [
        'visa_types' => 'array',
        'is_active'  => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
