<?php

namespace App\Modules\Service\Models;

use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalService extends BaseModel
{
    protected $table = 'global_services';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'is_combinable',
        'is_optional',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_combinable' => 'boolean',
        'is_optional'   => 'boolean',
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
    ];

    public function packageItems(): HasMany
    {
        return $this->hasMany(AgencyServicePackageItem::class, 'service_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
