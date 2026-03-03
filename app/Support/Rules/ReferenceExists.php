<?php

namespace App\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Валидирует что значение существует в справочнике reference_items.
 */
class ReferenceExists implements ValidationRule
{
    public function __construct(
        protected string $category,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $exists = DB::table('reference_items')
            ->where('category', $this->category)
            ->where('code', $value)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            $fail("Значение :attribute не найдено в справочнике '{$this->category}'.");
        }
    }
}
