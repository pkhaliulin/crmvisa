<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'        => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s\-\']+$/'],
            'last_name'         => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s\-\']+$/'],
            'email'             => ['required', 'email', Rule::unique('users', 'email')],
            'phone'             => ['nullable', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            'telegram_username' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9_]{3,32}$/'],
            'avatar'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'role'              => ['required', 'in:manager,partner'],
            'password'          => ['required', 'string', 'min:8'],
        ];
    }
}
