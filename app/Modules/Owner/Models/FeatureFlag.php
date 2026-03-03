<?php

namespace App\Modules\Owner\Models;

use App\Modules\Agency\Models\Agency;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'key',
        'name',
        'description',
        'enabled',
        'rollout_percent',
        'plans',
        'metadata',
    ];

    protected $casts = [
        'enabled'          => 'boolean',
        'rollout_percent'  => 'integer',
        'plans'            => 'array',
        'metadata'         => 'array',
    ];

    /**
     * Проверить, доступен ли флаг для данного агентства.
     */
    public function isEnabledFor(Agency $agency): bool
    {
        if (! $this->enabled) {
            return false;
        }

        // Проверка по планам
        if (! empty($this->plans)) {
            $agencyPlan = $agency->plan instanceof \BackedEnum
                ? $agency->plan->value
                : (string) $agency->plan;

            if (! in_array($agencyPlan, $this->plans, true)) {
                return false;
            }
        }

        // Rollout процент
        if ($this->rollout_percent < 100) {
            $hash = crc32($agency->id . $this->key);
            return ($hash % 100) < $this->rollout_percent;
        }

        return true;
    }
}
