<?php

namespace App\Http\Requests\Member;

use App\Models\People\MemberSacramentStatus;
use App\Support\PhoneNormalizer;
use App\Support\MemberMaritalStatuses;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
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

        $statuses = $this->input('sacrament_statuses');
        if (is_array($statuses)) {
            foreach (MemberSacramentStatus::TYPES as $type) {
                $certificateNo = data_get($statuses, $type.'.certificate_no');
                if (is_string($certificateNo)) {
                    data_set($statuses, $type.'.certificate_no', trim($certificateNo));
                }
            }

            $this->merge([
                'sacrament_statuses' => $statuses,
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
            'outstation_uuid' => ['nullable', 'uuid'],
            'zone_uuid' => ['nullable', 'uuid'],
            'jumuiya_uuid' => ['required', 'uuid'],
            'family_uuid' => ['required', 'uuid'],
            'family_relationship_uuid' => ['nullable', 'uuid'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', 'in:male,female'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX, Rule::unique('members', 'phone')],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['nullable', 'string', 'regex:/^\d{20}$/', Rule::unique('members', 'national_id')],
            'marital_status' => ['nullable', Rule::in(MemberMaritalStatuses::allowedValues())],
            'is_head_of_family' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sacrament_statuses' => ['nullable', 'array'],
            'sacrament_statuses.baptism' => ['nullable', 'array'],
            'sacrament_statuses.baptism.is_received' => ['nullable', 'boolean'],
            'sacrament_statuses.baptism.certificate_no' => ['nullable', 'string', 'max:120'],
            'sacrament_statuses.communion' => ['nullable', 'array'],
            'sacrament_statuses.communion.is_received' => ['nullable', 'boolean'],
            'sacrament_statuses.communion.certificate_no' => ['nullable', 'string', 'max:120'],
            'sacrament_statuses.confirmation' => ['nullable', 'array'],
            'sacrament_statuses.confirmation.is_received' => ['nullable', 'boolean'],
            'sacrament_statuses.confirmation.certificate_no' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $statuses = $this->input('sacrament_statuses', []);
            if (! is_array($statuses)) {
                return;
            }

            foreach (MemberSacramentStatus::TYPES as $type) {
                $certificateNo = data_get($statuses, $type.'.certificate_no');
                if (! is_string($certificateNo) || trim($certificateNo) === '') {
                    continue;
                }

                $exists = MemberSacramentStatus::query()
                    ->where('sacrament_type', $type)
                    ->where('certificate_no', trim($certificateNo))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        "sacrament_statuses.$type.certificate_no",
                        $this->duplicateCertificateMessage($type)
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'sacrament_statuses.baptism.certificate_no.max' => 'Baptism certificate number must not exceed 120 characters.',
            'sacrament_statuses.communion.certificate_no.max' => 'First Communion certificate number must not exceed 120 characters.',
            'sacrament_statuses.confirmation.certificate_no.max' => 'Confirmation certificate number must not exceed 120 characters.',
        ];
    }

    private function duplicateCertificateMessage(string $type): string
    {
        $label = match ($type) {
            MemberSacramentStatus::TYPE_BAPTISM => 'Baptism',
            MemberSacramentStatus::TYPE_COMMUNION => 'First Communion',
            MemberSacramentStatus::TYPE_CONFIRMATION => 'Confirmation',
            default => 'Sacrament',
        };

        return $label.' certificate number is already assigned to another member. Please review and enter the correct number.';
    }
}
