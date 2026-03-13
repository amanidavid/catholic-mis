<?php

namespace App\Http\Requests\ParishStaff;

use Illuminate\Foundation\Http\FormRequest;

class TransferParishStaffMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('parish-staff.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'zone_uuid' => ['nullable', 'uuid'],
            'jumuiya_uuid' => ['required', 'uuid'],
            'family_uuid' => ['required', 'uuid'],
            'effective_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
