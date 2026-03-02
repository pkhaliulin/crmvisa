<?php

namespace App\Support\Traits;

trait NormalizesPhone
{
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value !== null
            ? self::normalizePhone($value)
            : null;
    }

    public static function normalizePhone(string $phone): string
    {
        // Убираем пробелы, скобки, тире
        $phone = preg_replace('/[\s\-\(\)]+/', '', $phone);

        // Если начинается с цифры (не +), добавляем +
        if ($phone !== '' && $phone[0] !== '+' && ctype_digit($phone[0])) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}
