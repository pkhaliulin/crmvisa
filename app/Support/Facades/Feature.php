<?php

namespace App\Support\Facades;

use App\Modules\Agency\Models\Agency;
use App\Modules\Owner\Models\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class Feature
{
    /**
     * Проверить, включён ли feature flag для агентства.
     */
    public static function isEnabled(string $key, ?Agency $agency = null): bool
    {
        $flag = Cache::remember(
            "feature_flag:{$key}",
            now()->addMinutes(5),
            fn () => FeatureFlag::where('key', $key)->first()
        );

        if (! $flag) {
            return false;
        }

        if (! $agency) {
            return $flag->enabled;
        }

        return $flag->isEnabledFor($agency);
    }

    /**
     * Сбросить кэш конкретного флага.
     */
    public static function flush(string $key): void
    {
        Cache::forget("feature_flag:{$key}");
    }

    /**
     * Сбросить кэш всех флагов.
     */
    public static function flushAll(): void
    {
        $flags = FeatureFlag::pluck('key');
        foreach ($flags as $key) {
            Cache::forget("feature_flag:{$key}");
        }
    }
}
