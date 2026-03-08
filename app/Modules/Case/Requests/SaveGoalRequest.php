<?php

namespace App\Modules\Case\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year'           => ['required', 'integer', 'min:2024', 'max:2030'],
            'month'          => ['nullable', 'integer', 'min:1', 'max:12'],
            'target_clients' => ['nullable', 'integer', 'min:0'],
            'target_revenue' => ['nullable', 'integer', 'min:0'],
            'target_cases'   => ['nullable', 'integer', 'min:0'],
        ];
    }
}
