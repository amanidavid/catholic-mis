<?php

namespace App\Http\Requests\Finance\Accounting;

use Illuminate\Foundation\Http\FormRequest;

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
            'ledger_uuid' => ['nullable', 'string', 'size:36'],
            'debit_ledger_uuid' => ['required', 'string', 'size:36'],
            'credit_ledger_uuid' => ['required', 'string', 'size:36', 'different:debit_ledger_uuid'],
        ];
    }
}
