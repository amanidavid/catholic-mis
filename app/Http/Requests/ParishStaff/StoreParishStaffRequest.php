<?php

namespace App\Http\Requests\ParishStaff;

use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParishStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('parish-staff.create') ?? false;
    }

    public function rules(): array
    {
        $parishId = $this->user()?->parish_id;

        return [
            'member_uuid' => ['bail', 'nullable', 'uuid'],
            'jumuiya_uuid' => [
                'bail',
                'nullable',
                'uuid',
                Rule::prohibitedIf(fn () => (bool) $this->filled('member_uuid')),
            ],
            'gender' => [
                'bail',
                'nullable',
                Rule::in(['male', 'female']),
                'required_without:member_uuid',
                Rule::prohibitedIf(fn () => (bool) $this->filled('member_uuid')),
            ],
            'first_name' => [
                'bail',
                'nullable',
                'string',
                'max:255',
                'required_without:member_uuid',
                Rule::prohibitedIf(fn () => (bool) $this->filled('member_uuid')),
            ],
            'middle_name' => [
                'bail',
                'nullable',
                'string',
                'max:255',
                Rule::prohibitedIf(fn () => (bool) $this->filled('member_uuid')),
            ],
            'last_name' => [
                'bail',
                'nullable',
                'string',
                'max:255',
                'required_without:member_uuid',
                Rule::prohibitedIf(fn () => (bool) $this->filled('member_uuid')),
            ],
            'phone' => [
                'bail',
                'nullable',
                'string',
                'max:20',
                'regex:'.PhoneNormalizer::TZ_REGEX,
                Rule::unique('parish_staff', 'phone')->where(fn ($q) => $q->where('parish_id', $parishId)),
            ],
            'email' => [
                'bail',
                'nullable',
                'email',
                'max:255',
                Rule::unique('parish_staff', 'email')->where(fn ($q) => $q->where('parish_id', $parishId)),
            ],
            'national_id' => [
                'bail',
                'nullable',
                'string',
                'regex:/^\d{20}$/',
                Rule::unique('parish_staff', 'national_id')->where(fn ($q) => $q->where('parish_id', $parishId)),
            ],
            'notes' => ['bail', 'nullable', 'string', 'max:2000'],
            'is_active' => ['bail', 'sometimes', 'boolean'],
        ];
    }

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

        foreach (['first_name', 'middle_name', 'last_name', 'phone', 'email', 'national_id'] as $key) {
            $v = $this->input($key);
            if (is_string($v)) {
                $this->merge([$key => trim(strip_tags($v))]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'member_uuid.uuid' => 'Invalid member selected.',
            'jumuiya_uuid.uuid' => 'Invalid Christian Community selected.',
            'jumuiya_uuid.prohibited' => 'Christian Community is derived from the selected member.',
            'gender.in' => 'Gender must be Male or Female.',
            'gender.required_without' => 'Gender is required for external staff.',
            'gender.prohibited' => 'Gender is derived from the selected member.',
            'email.email' => 'Email address is invalid.',
            'first_name.required_without' => 'First name is required for external staff.',
            'last_name.required_without' => 'Last name is required for external staff.',
            'first_name.prohibited' => 'Do not enter names when a member is selected.',
            'middle_name.prohibited' => 'Do not enter names when a member is selected.',
            'last_name.prohibited' => 'Do not enter names when a member is selected.',
            'phone.regex' => 'Phone number format is invalid.',
            'phone.unique' => 'This phone number is already used by another staff in this parish.',
            'email.unique' => 'This email is already used by another staff in this parish.',
            'national_id.regex' => 'National ID must be exactly 20 digits.',
            'national_id.unique' => 'This national ID is already used by another staff in this parish.',
        ];
    }
}
