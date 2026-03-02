<?php

namespace App\Support\Traits;

trait NormalizesPhone
{
    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value !== null
            ? preg_replace('/\s+/', '', $value)
            : null;
    }
}
