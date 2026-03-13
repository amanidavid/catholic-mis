<?php

namespace App\Http\Requests\Zone;

use App\Models\Structure\Parish;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('zones.update') ?? false;
    }

    public function rules(): array
    {
        $parishId = Parish::query()->orderBy('id')->value('id');
        $zoneId = $this->route('zone')?->id;
        $currentYear = (int) now()->year;

        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                Rule::unique('zones', 'name')
                    ->where(fn ($q) => $q->where('parish_id', $parishId))
                    ->ignore($zoneId),
            ],
            'description' => ['bail', 'nullable', 'string', 'max:255'],
            'established_year' => ['bail', 'nullable', 'integer', 'min:1800', 'max:'.$currentYear],
            'is_active' => ['bail', 'required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['name', 'description'] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                $this->merge([$key => trim(strip_tags($value))]);
            }
        }
    }
}
