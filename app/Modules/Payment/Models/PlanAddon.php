<?php

namespace App\Modules\Payment\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasUuid;

class PlanAddon extends BaseModel
{
    use HasUuid;

    protected $fillable = [
        'slug', 'name', 'name_uz', 'description',
        'price_monthly_uzs', 'price_yearly_uzs', 'limits', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price_monthly_uzs' => 'integer',
        'price_yearly_uzs'  => 'integer',
        'limits'            => 'array',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
    ];
}
