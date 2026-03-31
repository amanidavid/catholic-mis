<?php

namespace App\Http\Requests\Finance\Banking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\Finance\BankTransactionTypes;

class StoreBankAccountTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_uuid' => ['required', 'string', 'size:36'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'transaction_type' => ['required', 'string', 'max:30', Rule::in(BankTransactionTypes::values())],
            'direction' => ['required', 'in:inflow,outflow'],
            'double_entry_uuid' => ['nullable', 'string', 'size:36'],
            'debit_ledger_uuid' => ['required', 'string', 'size:36'],
            'credit_ledger_uuid' => ['required', 'string', 'size:36', 'different:debit_ledger_uuid'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reference_no' => ['nullable', 'string', 'max:100', 'not_regex:/<[^>]*>/'],
            'description' => ['nullable', 'string', 'max:150', 'not_regex:/<[^>]*>/'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account_uuid.required' => 'Please select a bank account.',
            'bank_account_uuid.size' => 'The selected bank account is invalid.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'transaction_type.required' => 'Transaction type is required.',
            'transaction_type.max' => 'Transaction type must not exceed 30 characters.',
            'transaction_type.in' => 'Please choose a valid transaction type.',
            'direction.required' => 'Please choose whether this is an inflow or outflow.',
            'direction.in' => 'Direction must be either inflow or outflow.',
            'double_entry_uuid.size' => 'The selected posting rule is invalid.',
            'debit_ledger_uuid.required' => 'Debit ledger is required.',
            'debit_ledger_uuid.size' => 'The selected debit ledger is invalid.',
            'credit_ledger_uuid.required' => 'Credit ledger is required.',
            'credit_ledger_uuid.size' => 'The selected credit ledger is invalid.',
            'credit_ledger_uuid.different' => 'Debit ledger and credit ledger must be different.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.gt' => 'Amount must be greater than zero.',
            'reference_no.max' => 'Reference number must not exceed 100 characters.',
            'reference_no.not_regex' => 'Reference number must not contain HTML tags.',
            'description.max' => 'Description must not exceed 150 characters.',
            'description.not_regex' => 'Description must not contain HTML tags.',
        ];
    }
}
