<?php

namespace App\Http\Requests\Finance\ChartOfAccounts;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpsertAccountGroupsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['nullable', 'string', 'size:36'],
            'items.*.name' => ['required', 'string', 'min:2', 'max:30', 'not_regex:/<[^>]*>/', 'regex:/^[A-Za-z ]+$/u'],
            'items.*.code' => ['nullable', 'integer', 'min:1', 'max:255'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Please provide at least one account group.',
            'items.array' => 'Invalid payload format.',
            'items.*.name.required' => 'Group name is required.',
            'items.*.name.regex' => 'Group name must contain letters and spaces only.',
            'items.*.name.not_regex' => 'Group name must not contain HTML tags.',
            'items.*.code.nullable' => 'Group code is required.',
            'items.*.code.integer' => 'Group code must be a number.',
        ];
    }
}
