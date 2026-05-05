<?php

namespace App\Http\Requests\Outstation;

use App\Models\Structure\Parish;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOutstationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('outstations.update') ?? false;
    }

    public function rules(): array
    {
        $parishId = Parish::query()->orderBy('id')->value('id');
        $outstationId = $this->route('outstation')?->id;
        $currentYear = (int) now()->year;

        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                Rule::unique('outstations', 'name')
                    ->where(fn ($q) => $q->where('parish_id', $parishId))
                    ->ignore($outstationId),
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
