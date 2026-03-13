<?php

namespace App\Http\Requests\Zone;

use Illuminate\Foundation\Http\FormRequest;

class IndexZonesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('zones.view') || false;
    }

    public function rules(): array
    {
        return [
            'q' => ['bail', 'nullable', 'string', 'max:100'],
            'per_page' => ['bail', 'nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $q = $this->input('q');

        $this->merge([
            'q' => is_string($q) ? trim(strip_tags($q)) : $q,
        ]);
    }
}
