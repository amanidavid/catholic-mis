<?php

namespace App\Http\Requests\Finance\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\Finance\BankTransactionTypes;

class StoreDoubleEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:120', 'not_regex:/<[^>]*>/'],
            'transaction_type' => ['nullable', 'string', 'max:30', Rule::in(BankTransactionTypes::values())],
            'ledger_uuid' => ['nullable', 'string', 'size:36', 'required_with:transaction_type'],
            'debit_ledger_uuid' => ['required', 'string', 'size:36'],
            'credit_ledger_uuid' => ['required', 'string', 'size:36', 'different:debit_ledger_uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_type.in' => 'Please select a valid transaction type.',
            'ledger_uuid.required_with' => 'Lookup ledger is required when transaction type is selected.',
            'credit_ledger_uuid.different' => 'Debit ledger and credit ledger must be different.',
        ];
    }
}
