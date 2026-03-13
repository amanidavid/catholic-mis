<?php

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

class IndexFamiliesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('families.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'jumuiya_uuid' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
