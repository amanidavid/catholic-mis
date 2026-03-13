<?php

namespace App\Http\Requests\ParishStaff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParishStaffAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('parish-staff.assignments.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'role_uuid' => ['bail', 'required', 'uuid'],
            'institution_uuid' => ['bail', 'nullable', 'uuid'],
            'title' => ['bail', 'nullable', 'string', 'max:255'],
            'start_date' => ['bail', 'required', 'date'],
            'end_date' => ['bail', 'nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['bail', 'required', 'boolean'],
            'notes' => ['bail', 'nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['title'] as $key) {
            $v = $this->input($key);
            if (is_string($v)) {
                $this->merge([$key => trim(strip_tags($v))]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'role_uuid.required' => 'Assignment role is required.',
            'role_uuid.uuid' => 'Invalid assignment role selected.',
            'end_date.after_or_equal' => 'End date must be on or after start date.',
        ];
    }
}
