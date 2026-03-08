<?php

namespace App\Modules\Client\Requests;

use App\Support\Rules\ReferenceExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['nullable', 'email', 'max:255'],
            'phone'               => ['required', 'string', 'max:30'],
            'telegram_chat_id'    => ['nullable', 'string', 'max:50'],
            'passport_number'     => ['nullable', 'string', 'max:30'],
            'nationality'         => ['nullable', 'string', 'size:3'],
            'date_of_birth'       => ['nullable', 'date'],
            'passport_expires_at' => ['nullable', 'date'],
            'source'              => ['nullable', new ReferenceExists('lead_source')],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
