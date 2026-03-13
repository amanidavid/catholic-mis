<?php

namespace App\Http\Requests\Jumuiya;

use Illuminate\Foundation\Http\FormRequest;

class IndexJumuiyasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('jumuiyas.view') || false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'zone_uuid' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => is_string($this->q) ? trim($this->q) : $this->q,
        ]);
    }
}
