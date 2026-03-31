<?php

namespace App\Http\Requests\Finance\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class StorePettyCashReplenishmentRequest extends FormRequest
{
    private const SAFE_TEXT_REGEX = "/^[\\pL\\pN\\s\\-\\.,&()\\/'#:]+$/u";

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference_no' => is_string($this->reference_no) ? strtoupper(trim($this->reference_no)) : $this->reference_no,
            'description' => is_string($this->description) ? trim(preg_replace('/\s+/u', ' ', $this->description)) : $this->description,
        ]);
    }

    public function rules(): array
    {
        return [
            'petty_cash_fund_uuid' => ['required', 'string', 'size:36'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'source_ledger_uuid' => ['required', 'string', 'size:36'],
            'reference_no' => ['nullable', 'string', 'max:100', 'regex:/^[A-Z0-9\\-\\/_#\\.]+$/', 'not_regex:/<[^>]*>/'],
            'description' => ['nullable', 'string', 'max:150', 'regex:' . self::SAFE_TEXT_REGEX, 'not_regex:/<[^>]*>/'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'petty_cash_fund_uuid.required' => 'Please select the petty cash fund.',
            'petty_cash_fund_uuid.size' => 'The selected petty cash fund is invalid.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'source_ledger_uuid.required' => 'Please select the source ledger.',
            'source_ledger_uuid.size' => 'The selected source ledger is invalid.',
            'reference_no.max' => 'Reference number must not exceed 100 characters.',
            'reference_no.regex' => 'Reference number contains invalid characters.',
            'reference_no.not_regex' => 'Reference number must not contain HTML or script tags.',
            'description.max' => 'Description must not exceed 150 characters.',
            'description.regex' => 'Description contains invalid characters.',
            'description.not_regex' => 'Description must not contain HTML or script tags.',
            'amount.required' => 'Replenishment amount is required.',
            'amount.numeric' => 'Replenishment amount must be a valid number.',
            'amount.gt' => 'Replenishment amount must be greater than zero.',
        ];
    }
}
