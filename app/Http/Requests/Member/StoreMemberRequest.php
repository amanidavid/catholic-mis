<?php

namespace App\Http\Requests\Member;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        if (is_string($phone)) {
            $this->merge([
                'phone' => PhoneNormalizer::normalize($phone),
            ]);
        }

        $nationalId = $this->input('national_id');
        if (is_string($nationalId)) {
            $this->merge([
                'national_id' => preg_replace('/\D+/', '', $nationalId) ?: null,
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('members.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'zone_uuid' => ['nullable', 'uuid'],
            'jumuiya_uuid' => ['required', 'uuid'],
            'family_uuid' => ['required', 'uuid'],
            'family_relationship_uuid' => ['nullable', 'uuid'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+255|0)?[67]\d{8}$/', Rule::unique('members', 'phone')],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'regex:/^\d{20}$/', Rule::unique('members', 'national_id')],
            'marital_status' => ['nullable', 'in:single,married,widowed,divorced'],
            'is_head_of_family' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
