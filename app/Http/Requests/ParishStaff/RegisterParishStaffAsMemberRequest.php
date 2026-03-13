<?php

namespace App\Http\Requests\ParishStaff;

use Illuminate\Foundation\Http\FormRequest;

class RegisterParishStaffAsMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('parish-staff.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'jumuiya_uuid' => ['nullable', 'uuid'],
        ];
    }
}
