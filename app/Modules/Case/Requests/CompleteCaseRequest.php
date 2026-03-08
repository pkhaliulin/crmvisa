<?php

namespace App\Modules\Case\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'result_type'            => ['required', 'in:approved,rejected'],
            'result_notes'           => ['nullable', 'string'],
            'visa_issued_at'         => ['nullable', 'date'],
            'visa_received_at'       => ['nullable', 'date'],
            'visa_validity'          => ['nullable', 'string', 'max:100'],
            'rejection_reason'       => ['nullable', 'string'],
            'can_reapply'            => ['nullable', 'boolean'],
            'reapply_recommendation' => ['nullable', 'string'],
        ];
    }
}
