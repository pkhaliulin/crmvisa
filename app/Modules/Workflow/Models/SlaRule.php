<?php

namespace App\Modules\Workflow\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SlaRule extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'country_code',
        'visa_type',
        'min_days',
        'max_days',
        'warning_days',
        'is_active',
        'stage_sla_days',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'stage_sla_days' => 'array',
    ];

    public static function findRule(string $countryCode, string $visaType): ?self
    {
        return self::where('country_code', $countryCode)
            ->where('visa_type', $visaType)
            ->where('is_active', true)
            ->first();
    }
}
