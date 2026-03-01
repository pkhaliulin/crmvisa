<?php

namespace App\Modules\Service\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyServicePackageItem extends Model
{
    use HasUuid;

    protected $table = 'agency_service_package_items';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'package_id',
        'service_id',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(AgencyServicePackage::class, 'package_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(GlobalService::class, 'service_id');
    }
}
