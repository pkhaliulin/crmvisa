<?php

namespace App\Modules\Client\Requests;

use App\Support\Rules\ReferenceExists;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['sometimes', 'string', 'max:255'],
            'email'               => ['sometimes', 'nullable', 'email'],
            'phone'               => ['sometimes', 'nullable', 'string', 'max:30'],
            'telegram_chat_id'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'passport_number'     => ['sometimes', 'nullable', 'string', 'max:30'],
            'nationality'         => ['sometimes', 'nullable', 'string', 'size:3'],
            'date_of_birth'       => ['sometimes', 'nullable', 'date'],
            'passport_expires_at' => ['sometimes', 'nullable', 'date'],
            'source'              => ['sometimes', new ReferenceExists('lead_source')],
            'notes'               => ['sometimes', 'nullable', 'string'],
        ];
    }
}
