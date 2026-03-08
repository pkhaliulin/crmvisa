<?php

namespace App\Modules\Case\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $agencyId = $this->user()->agency_id;

        return [
            'client_id'     => ['required', 'uuid', "exists:clients,id,agency_id,{$agencyId}"],
            'country_code'  => ['required', 'string', 'size:2'],
            'visa_type'     => ['required', 'string', 'max:50', 'exists:portal_visa_types,slug'],
            'assigned_to'   => ['nullable', 'uuid', "exists:users,id,agency_id,{$agencyId}"],
            'priority'      => ['nullable', 'in:low,normal,high,urgent'],
            'critical_date' => ['nullable', 'date'],
            'travel_date'   => ['nullable', 'date'],
            'notes'         => ['nullable', 'string'],
        ];
    }
}
