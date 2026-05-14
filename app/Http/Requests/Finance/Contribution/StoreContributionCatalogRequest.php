<?php

namespace App\Http\Requests\Finance\Contribution;

use Illuminate\Foundation\Http\FormRequest;

class StoreContributionCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80', 'not_regex:/<[^>]*>/'],
            'code' => ['required', 'string', 'min:2', 'max:30', 'not_regex:/<[^>]*>/'],
            'description' => ['nullable', 'string', 'max:250', 'not_regex:/<[^>]*>/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Contribution name is required.',
            'name.min' => 'Contribution name must be at least 2 characters.',
            'name.max' => 'Contribution name must not exceed 80 characters.',
            'name.not_regex' => 'Contribution name must not contain HTML tags.',
            'code.required' => 'Contribution code is required.',
            'code.min' => 'Contribution code must be at least 2 characters.',
            'code.max' => 'Contribution code must not exceed 30 characters.',
            'code.not_regex' => 'Contribution code must not contain HTML tags.',
            'description.max' => 'Description must not exceed 250 characters.',
            'description.not_regex' => 'Description must not contain HTML tags.',
            'is_active.boolean' => 'Status must be valid.',
        ];
    }
}
