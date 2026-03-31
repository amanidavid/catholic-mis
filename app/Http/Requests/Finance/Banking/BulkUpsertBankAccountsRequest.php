<?php

namespace App\Http\Requests\Finance\Banking;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpsertBankAccountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_uuid' => ['required', 'string', 'size:36'],
            'currency_uuid' => ['required', 'string', 'size:36'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['nullable', 'string', 'size:36'],
            'items.*.ledger_uuid' => ['required', 'string', 'size:36'],
            'items.*.account_name' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/<[^>]*>/'],
            'items.*.account_number' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9\\-\\/ ]+$/'],
            'items.*.branch' => ['nullable', 'string', 'max:80', 'not_regex:/<[^>]*>/'],
            'items.*.swift_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_uuid.required' => 'Please select a bank.',
            'bank_uuid.size' => 'The selected bank is invalid.',
            'currency_uuid.required' => 'Please select a currency.',
            'currency_uuid.size' => 'The selected currency is invalid.',
            'items.required' => 'Please provide at least one bank account.',
            'items.array' => 'Invalid bank account payload.',
            'items.min' => 'Please add at least one bank account row.',
            'items.*.uuid.size' => 'One of the selected bank account records is invalid.',
            'items.*.ledger_uuid.required' => 'Please select a ledger for each bank account.',
            'items.*.ledger_uuid.size' => 'One of the selected ledgers is invalid.',
            'items.*.account_name.required' => 'Account name is required.',
            'items.*.account_name.min' => 'Account name must be at least 2 characters.',
            'items.*.account_name.max' => 'Account name must not exceed 120 characters.',
            'items.*.account_name.not_regex' => 'Account name must not contain HTML tags.',
            'items.*.account_number.required' => 'Account number is required.',
            'items.*.account_number.min' => 'Account number must be at least 3 characters.',
            'items.*.account_number.max' => 'Account number must not exceed 40 characters.',
            'items.*.account_number.regex' => 'Account number may contain only letters, numbers, spaces, dashes, and slashes.',
            'items.*.branch.max' => 'Branch must not exceed 80 characters.',
            'items.*.branch.not_regex' => 'Branch must not contain HTML tags.',
            'items.*.swift_code.max' => 'Swift code must not exceed 20 characters.',
            'items.*.swift_code.regex' => 'Swift code may contain letters and numbers only.',
            'items.*.is_active.boolean' => 'Bank account status must be valid.',
        ];
    }
}
