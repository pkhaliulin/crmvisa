<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public $incrementing  = false;

    protected $fillable = ['key', 'value', 'type', 'group', 'description'];

    private static int $cacheTtl = 3600;

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('platform_settings', self::$cacheTtl, function () {
            return self::pluck('value', 'key')->toArray();
        });

        $raw = $settings[$key] ?? null;
        if ($raw === null) return $default;

        $model = self::where('key', $key)->first();
        $type  = $model?->type ?? 'string';

        return match ($type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'decimal' => (float) $raw,
            'json'    => json_decode($raw, true),
            default   => $raw,
        };
    }

    public static function set(string $key, mixed $value): void
    {
        $stringValue = is_bool($value) ? ($value ? 'true' : 'false')
                     : (is_array($value) ? json_encode($value) : (string) $value);

        self::updateOrCreate(['key' => $key], ['value' => $stringValue, 'updated_at' => now()]);
        Cache::forget('platform_settings');
    }

    public static function getByGroup(string $group): array
    {
        return self::where('group', $group)->get()
            ->mapWithKeys(fn ($s) => [$s->key => self::get($s->key)])
            ->toArray();
    }

    public static function clearCache(): void
    {
        Cache::forget('platform_settings');
    }
}
