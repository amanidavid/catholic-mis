<?php

namespace App\Http\Requests\Finance\Contribution;

use Illuminate\Foundation\Http\FormRequest;

class StoreContributionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contribution_catalog_uuid' => ['required', 'string', 'exists:contribution_catalogs,uuid'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'is_required' => ['nullable', 'boolean'],
            'allow_partial_payment' => ['nullable', 'boolean'],
            'allow_override' => ['nullable', 'boolean'],
            'waiver_allowed' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'contribution_catalog_uuid.required' => 'Contribution type is required.',
            'contribution_catalog_uuid.exists' => 'Selected contribution type is invalid.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least 0.',
            'currency_code.size' => 'Currency code must be 3 characters.',
            'effective_to.after_or_equal' => 'Effective to date must be after or equal to effective from date.',
            'sort_order.integer' => 'Sort order must be a valid number.',
            'sort_order.min' => 'Sort order must be at least 0.',
        ];
    }
}
