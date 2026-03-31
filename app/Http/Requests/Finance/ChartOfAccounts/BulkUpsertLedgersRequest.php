<?php

namespace App\Http\Requests\Finance\ChartOfAccounts;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpsertLedgersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_subtype_uuid' => ['required', 'string', 'size:36'],
            'currency_uuid' => ['required', 'string', 'size:36'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.uuid' => ['nullable', 'string', 'size:36'],
            'items.*.name' => ['required', 'string', 'min:2', 'max:80', 'not_regex:/<[^>]*>/', 'regex:/^[A-Za-z ]+$/u'],
            'items.*.account_code' => ['nullable', 'string', 'max:50', 'not_regex:/<[^>]*>/'],
            'items.*.opening_balance' => ['nullable', 'numeric'],
            'items.*.opening_balance_type' => ['nullable', 'in:debit,credit'],
            'items.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_subtype_uuid.required' => 'Account subtype is required.',
            'currency_uuid.required' => 'Currency is required.',
            'items.required' => 'Please provide at least one ledger.',
            'items.*.name.required' => 'Ledger name is required.',
            'items.*.name.regex' => 'Ledger name must contain letters and spaces only.',
            'items.*.name.not_regex' => 'Ledger name must not contain HTML tags.',
            'items.*.opening_balance_type.in' => 'Opening balance type must be debit or credit.',
        ];
    }
}
