<?php

namespace App\Http\Requests\Institution;

use Illuminate\Foundation\Http\FormRequest;

class IndexInstitutionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('institutions.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $q = $this->query('q');
        if (is_string($q)) {
            $this->merge(['q' => trim($q)]);
        }
    }
}
