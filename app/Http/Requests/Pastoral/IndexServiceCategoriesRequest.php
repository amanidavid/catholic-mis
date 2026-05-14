<?php

namespace App\Http\Requests\Pastoral;

use Illuminate\Foundation\Http\FormRequest;

class IndexServiceCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('service-categories.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
