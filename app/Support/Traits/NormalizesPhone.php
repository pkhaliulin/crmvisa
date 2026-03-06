<?php

namespace App\Support\Traits;

/**
 * Нормализация телефона: убирает пробелы/скобки/тире, добавляет +.
 *
 * Совместим с `'phone' => 'encrypted'` cast — использует saving event
 * вместо setPhoneAttribute, чтобы не обходить шифрование.
 */
trait NormalizesPhone
{
    public static function bootNormalizesPhone(): void
    {
        static::saving(function ($model) {
            if ($model->isDirty('phone')) {
                $phone = $model->phone; // getAttribute → decrypt если encrypted
                if ($phone !== null) {
                    $normalized = static::normalizePhone($phone);
                    if ($normalized !== $phone) {
                        $model->phone = $normalized; // setAttribute → encrypt если encrypted
                    }
                }
            }
        });
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
