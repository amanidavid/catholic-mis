<?php

namespace App\Http\Requests\Outstation;

use Illuminate\Foundation\Http\FormRequest;

class IndexOutstationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('outstations.view') || false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
