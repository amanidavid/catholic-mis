<?php

namespace App\Http\Requests\Outstation;

use App\Models\Structure\Outstation;
use App\Models\Structure\Parish;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOutstationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('outstations.create') ?? false;
    }

    public function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'outstations' => ['bail', 'required', 'array', 'min:1', 'max:200'],
            'outstations.*.name' => ['bail', 'required', 'string', 'max:255'],
            'outstations.*.description' => ['bail', 'nullable', 'string', 'max:255'],
            'outstations.*.established_year' => ['bail', 'nullable', 'integer', 'min:1800', 'max:'.$currentYear],
            'outstations.*.is_active' => ['bail', 'nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $outstations = $this->input('outstations');

        if (! is_array($outstations)) {
            return;
        }

        $cleaned = [];
        foreach ($outstations as $outstation) {
            $cleaned[] = [
                'name' => is_string($outstation['name'] ?? null) ? trim(strip_tags($outstation['name'])) : ($outstation['name'] ?? null),
                'description' => is_string($outstation['description'] ?? null) ? trim(strip_tags($outstation['description'])) : ($outstation['description'] ?? null),
                'established_year' => $outstation['established_year'] ?? null,
                'is_active' => array_key_exists('is_active', $outstation) ? $outstation['is_active'] : true,
            ];
        }

        $this->merge(['outstations' => $cleaned]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $outstations = $this->input('outstations');

            if (! is_array($outstations)) {
                return;
            }

            $names = [];
            $rawNames = [];
            foreach ($outstations as $i => $outstation) {
                $name = $outstation['name'] ?? null;
                if (! is_string($name) || trim($name) === '') {
                    continue;
                }

                $trimmed = trim($name);
                $key = mb_strtolower($trimmed);
                if (isset($names[$key])) {
                    $v->errors()->add("outstations.$i.name", 'Duplicate outstation name in this request.');
                }

                $names[$key] = true;
                $rawNames[] = $trimmed;
            }

            if (empty($rawNames)) {
                return;
            }

            $parishId = Parish::query()->value('id');
            if (! $parishId) {
                return;
            }

            $existing = Outstation::query()
                ->where('parish_id', $parishId)
                ->whereIn('name', $rawNames)
                ->pluck('name')
                ->map(fn ($n) => mb_strtolower(trim((string) $n)))
                ->unique()
                ->values()
                ->all();

            foreach ($outstations as $i => $outstation) {
                $name = $outstation['name'] ?? null;
                if (! is_string($name) || trim($name) === '') {
                    continue;
                }

                if (in_array(mb_strtolower(trim($name)), $existing, true)) {
                    $v->errors()->add("outstations.$i.name", 'Outstation name already exists.');
                }
            }
        });
    }
}
