<?php

namespace App\Http\Requests\Leadership;

use Illuminate\Foundation\Http\FormRequest;

class StoreJumuiyaLeadershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('jumuiya-leaderships.create');
    }

    public function rules(): array
    {
        return [
            'jumuiya_uuid' => ['required', 'uuid'],
            'member_uuid' => ['required', 'uuid'],
            'role_uuid' => ['required', 'uuid'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
            'create_login' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_login' => filter_var($this->input('create_login', false), FILTER_VALIDATE_BOOL),
            'is_active' => $this->has('is_active')
                ? filter_var($this->input('is_active'), FILTER_VALIDATE_BOOL)
                : null,
        ]);
    }
}
