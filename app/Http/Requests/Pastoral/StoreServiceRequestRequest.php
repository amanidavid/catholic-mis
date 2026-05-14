<?php

namespace App\Http\Requests\Pastoral;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service-requests.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'request_date' => is_string($this->input('request_date')) ? trim($this->input('request_date')) : $this->input('request_date'),
            'preferred_service_date' => is_string($this->input('preferred_service_date')) ? trim($this->input('preferred_service_date')) : $this->input('preferred_service_date'),
        ]);
    }

    public function rules(): array
    {
        return [
            'jumuiya_uuid' => ['required', 'uuid'],
            'request_date' => ['required', 'date_format:Y-m-d'],
            'preferred_service_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'urgency' => ['nullable', 'in:low,normal,high,urgent'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'families' => ['required', 'array', 'min:1', 'max:100'],
            'families.*.family_uuid' => ['required', 'uuid'],
            'families.*.family_notes' => ['nullable', 'string', 'max:2000'],
            'families.*.items' => ['required', 'array', 'min:1', 'max:20'],
            'families.*.items.*.service_category_uuid' => ['required', 'uuid'],
            'families.*.items.*.target_member_uuid' => ['nullable', 'uuid'],
            'families.*.items.*.description' => ['nullable', 'string', 'max:2000'],
            'families.*.items.*.requested_for_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_date.required' => 'Request date is required.',
            'request_date.date_format' => 'Request date must be a valid date in YYYY-MM-DD format. Past, present, and future dates are allowed.',
            'preferred_service_date.date_format' => 'Preferred service date must be a valid date in YYYY-MM-DD format.',
            'preferred_service_date.after_or_equal' => 'Preferred service date must be today or a future date.',
            'families.*.items.*.requested_for_date.date_format' => 'Requested-for date must be a valid date in YYYY-MM-DD format.',
            'families.*.items.*.requested_for_date.after_or_equal' => 'Requested-for date must be today or a future date.',
        ];
    }
}
