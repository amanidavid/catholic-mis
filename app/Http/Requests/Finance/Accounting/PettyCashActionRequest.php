<?php

namespace App\Http\Requests\Finance\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class PettyCashActionRequest extends FormRequest
{
    private const SAFE_TEXT_REGEX = "/^[\\pL\\pN\\s\\-\\.,&()\\/'#:]+$/u";

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => is_string($this->reason) ? trim(preg_replace('/\s+/u', ' ', $this->reason)) : $this->reason,
        ]);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:200', 'regex:' . self::SAFE_TEXT_REGEX, 'not_regex:/<[^>]*>/'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Reason is required.',
            'reason.max' => 'Reason must not exceed 200 characters.',
            'reason.regex' => 'Reason contains invalid characters.',
            'reason.not_regex' => 'Reason must not contain HTML or script tags.',
        ];
    }
}
