<?php

namespace App\Http\Requests\ParishStaff;

use App\Models\ParishStaffAssignmentRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateParishStaffPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->can('parish-staff-positions.update');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $parishId = (int) ($this->user()?->parish_id ?? 0);
                    if (! $parishId) {
                        return;
                    }

                    $role = $this->route('role');
                    $roleId = $role instanceof ParishStaffAssignmentRole ? (int) $role->id : null;

                    $key = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? ''), 'UTF-8');
                    if ($key === '') {
                        return;
                    }

                    $query = ParishStaffAssignmentRole::query()
                        ->where('parish_id', $parishId)
                        ->where('name_key', $key);

                    if ($roleId) {
                        $query->where('id', '!=', $roleId);
                    }

                    if ($query->exists()) {
                        $fail('Position name already exists.');
                    }
                },
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'is_active' => $this->has('is_active')
                ? filter_var($this->input('is_active'), FILTER_VALIDATE_BOOL)
                : null,
        ]);
    }
}
