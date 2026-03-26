<?php

namespace App\Http\Requests\Finance\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:150', 'not_regex:/<[^>]*>/'],

            // Quick journal (double entry mapping): user selects a ledger, system resolves debit/credit ledgers.
            'quick_ledger_uuid' => ['nullable', 'string', 'size:36', 'required_without:lines'],
            'amount' => ['nullable', 'numeric', 'min:0.0001', 'required_with:quick_ledger_uuid'],

            // Manual journal lines.
            'lines' => ['nullable', 'array', 'min:2', 'required_without:quick_ledger_uuid'],
            'lines.*.ledger_uuid' => ['required', 'string', 'size:36'],
            'lines.*.description' => ['nullable', 'string', 'max:150', 'not_regex:/<[^>]*>/'],
            'lines.*.debit_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Transaction date is required.',
            'lines.required' => 'Please add at least two journal lines.',
            'lines.min' => 'Please add at least two journal lines.',
            'lines.*.ledger_uuid.required' => 'Ledger is required for each line.',
        ];
    }
}
