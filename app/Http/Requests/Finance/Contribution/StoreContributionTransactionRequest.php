<?php

namespace App\Http\Requests\Finance\Contribution;

use Illuminate\Foundation\Http\FormRequest;

class StoreContributionTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contribution_payment_request_uuid' => ['required', 'string', 'exists:contribution_payment_requests,uuid'],
            'member_uuid' => ['nullable', 'string', 'exists:members,uuid'],
            'transaction_type' => ['required', 'string', 'in:payment,waiver,refund,adjustment'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank,mobile,other'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500', 'not_regex:/<[^>]*>/'],
        ];
    }

    public function messages(): array
    {
        return [
            'contribution_payment_request_uuid.required' => 'Payment request is required.',
            'contribution_payment_request_uuid.exists' => 'Selected payment request is invalid.',
            'member_uuid.exists' => 'Selected member is invalid.',
            'transaction_type.required' => 'Transaction type is required.',
            'transaction_type.in' => 'Invalid transaction type.',
            'amount.required' => 'Amount is required.',
            'amount.numeric' => 'Amount must be a valid number.',
            'amount.min' => 'Amount must be at least 0.',
            'payment_method.in' => 'Invalid payment method.',
            'reference_no.max' => 'Reference number must not exceed 100 characters.',
            'notes.max' => 'Notes must not exceed 500 characters.',
            'notes.not_regex' => 'Notes must not contain HTML tags.',
        ];
    }
}
