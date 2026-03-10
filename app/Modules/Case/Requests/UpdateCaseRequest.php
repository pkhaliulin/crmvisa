<?php

namespace App\Modules\Case\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;

        return [
            'assigned_to'          => ['sometimes', 'nullable', 'uuid', "exists:users,id,agency_id,{$agencyId}"],
            'priority'             => ['sometimes', 'in:low,normal,high,urgent'],
            'critical_date'        => ['sometimes', 'nullable', 'date'],
            'travel_date'          => ['sometimes', 'nullable', 'date', 'after_or_equal:today'],
            'notes'                => ['sometimes', 'nullable', 'string'],
            'appointment_date'     => ['sometimes', 'nullable', 'date'],
            'appointment_time'     => ['sometimes', 'nullable', 'string', 'max:10'],
            'appointment_location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'expected_result_date' => ['sometimes', 'nullable', 'date'],
            'return_date'          => ['sometimes', 'nullable', 'date'],
        ];
    }
}
