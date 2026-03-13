<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('families.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'zone_uuid' => ['nullable', 'uuid'],
            'jumuiya_uuid' => ['sometimes', 'uuid'],
            'family_name' => ['required', 'string', 'max:255'],
            'family_code' => ['nullable', 'string', 'max:50'],
            'house_number' => ['nullable', 'string', 'max:50'],
            'street' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
