<?php

namespace App\Support\Traits;

/**
 * Автоматическая нормализация имени: Title Case + trim.
 * "john DOE" -> "John Doe"
 */
trait NormalizesName
{
    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value !== null ? self::normalizeName($value) : null;
    }

    public static function normalizeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }
}
