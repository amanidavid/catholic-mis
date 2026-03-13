<?php

namespace App\Http\Requests\ParishStaff;

use Illuminate\Foundation\Http\FormRequest;

class IndexParishStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('parish-staff.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['bail', 'nullable', 'string', 'max:100'],
            'search_by' => ['bail', 'nullable', 'string', 'in:name,phone,email,national_id,assignment_type'],
            'is_active' => ['bail', 'nullable', 'string', 'in:all,active,inactive'],
            'per_page' => ['bail', 'nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['q', 'search_by', 'is_active'] as $key) {
            $v = $this->input($key);
            if (is_string($v)) {
                $this->merge([$key => trim(strip_tags($v))]);
            }
        }
    }
}
