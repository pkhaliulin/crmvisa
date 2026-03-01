<?php

namespace App\Modules\Service\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AgencyServicePackage extends BaseModel
{
    use HasTenant;

    protected $table = 'agency_service_packages';

    protected $fillable = [
        'agency_id',
        'country_code',
        'visa_type',
        'name',
        'description',
        'price',
        'currency',
        'processing_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price'           => 'float',
        'processing_days' => 'integer',
        'sort_order'      => 'integer',
        'is_active'       => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(AgencyServicePackageItem::class, 'package_id')->orderBy('sort_order');
    }

    public function services(): HasManyThrough
    {
        return $this->hasManyThrough(
            GlobalService::class,
            AgencyServicePackageItem::class,
            'package_id',
            'id',
            'id',
            'service_id'
        );
    }
}
