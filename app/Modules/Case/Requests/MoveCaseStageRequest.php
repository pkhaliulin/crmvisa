<?php

namespace App\Modules\Case\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveCaseStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stage' => ['required', 'string', 'in:' . implode(',', array_keys(config('stages')))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
