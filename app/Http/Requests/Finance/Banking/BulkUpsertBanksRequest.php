<?php

namespace App\Http\Requests\Finance\Banking;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpsertBanksRequest extends FormRequest
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
            'items.*.name' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/<[^>]*>/'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Please provide at least one bank.',
            'items.array' => 'Invalid payload format.',
            'items.min' => 'Please add at least one bank.',
            'items.*.uuid.size' => 'One of the selected bank records is invalid.',
            'items.*.name.required' => 'Bank name is required.',
            'items.*.name.min' => 'Bank name must be at least 2 characters.',
            'items.*.name.max' => 'Bank name must not exceed 120 characters.',
            'items.*.name.not_regex' => 'Bank name must not contain HTML tags.',
            'items.*.is_active.boolean' => 'Bank status must be valid.',
        ];
    }
}
