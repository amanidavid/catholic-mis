<?php

namespace App\Http\Requests\Pastoral;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeceasedRegisterEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('deceased-register.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'member_uuid' => ['required', 'uuid'],
            'date_of_death' => ['required', 'date'],
            'time_of_death' => ['nullable', 'date_format:H:i'],
            'place_of_death' => ['required', 'string', 'max:255'],
            'cause_of_death' => ['nullable', 'string', 'max:4000'],
            'death_certificate_number' => ['nullable', 'string', 'max:120'],
            'hospital_or_health_facility' => ['nullable', 'string', 'max:255'],
            'funeral_date' => ['nullable', 'date'],
            'burial_date' => ['nullable', 'date', 'after_or_equal:date_of_death'],
            'burial_location_or_cemetery' => ['nullable', 'string', 'max:255'],
            'funeral_mass_location' => ['nullable', 'string', 'max:255'],
            'priest_or_celebrant_name' => ['nullable', 'string', 'max:255'],
            'homily_or_remarks' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
