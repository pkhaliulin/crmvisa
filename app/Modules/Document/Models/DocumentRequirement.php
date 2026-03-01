<?php

namespace App\Modules\Document\Models;

use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class DocumentRequirement extends BaseModel
{
    use \App\Support\Traits\HasUuid;

    protected $table = 'document_requirements';

    protected $fillable = [
        'country_code',
        'visa_type',
        'name',
        'description',
        'is_required',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
    ];

    /**
     * Получить список требований для страны+типа визы.
     * Включает универсальные (* = все страны/типы) + специфичные.
     */
    public static function forCase(string $countryCode, string $visaType): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->where(function (Builder $q) use ($countryCode, $visaType) {
                // Универсальные для всех стран и типов
                $q->where(fn ($q) => $q->where('country_code', '*')->where('visa_type', '*'))
                  // Специфичные для страны, любой тип
                  ->orWhere(fn ($q) => $q->where('country_code', $countryCode)->where('visa_type', '*'))
                  // Универсальные для типа, любая страна
                  ->orWhere(fn ($q) => $q->where('country_code', '*')->where('visa_type', $visaType))
                  // Точное совпадение
                  ->orWhere(fn ($q) => $q->where('country_code', $countryCode)->where('visa_type', $visaType));
            })
            ->orderBy('sort_order')
            ->get();
    }
}
