<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class IndexMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('members.view') ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'search_by' => ['nullable', 'string', 'in:name,phone,email,national_id'],
            'jumuiya_uuid' => ['nullable', 'uuid'],
            'family_uuid' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
