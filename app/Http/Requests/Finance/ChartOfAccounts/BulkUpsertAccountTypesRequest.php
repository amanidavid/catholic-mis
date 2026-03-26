<?php

namespace App\Http\Requests\Finance\ChartOfAccounts;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpsertAccountTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_group_uuid' => ['required', 'string', 'size:36'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['nullable', 'string', 'size:36'],
            'items.*.name' => ['required', 'string', 'min:2', 'max:60', 'not_regex:/<[^>]*>/', 'regex:/^[A-Za-z ]+$/u'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_group_uuid.required' => 'Account group is required.',
            'items.required' => 'Please provide at least one account type.',
            'items.*.name.required' => 'Type name is required.',
            'items.*.name.regex' => 'Type name must contain letters and spaces only.',
            'items.*.name.not_regex' => 'Type name must not contain HTML tags.',
        ];
    }
}
