<?php

namespace App\Http\Requests\Zone;

use App\Models\Structure\Parish;
use App\Models\Structure\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreZonesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('zones.create') ?? false;
    }

    public function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'zones' => ['bail', 'required', 'array', 'min:1', 'max:200'],
            'zones.*.name' => ['bail', 'required', 'string', 'max:255'],
            'zones.*.description' => ['bail', 'nullable', 'string', 'max:255'],
            'zones.*.established_year' => ['bail', 'nullable', 'integer', 'min:1800', 'max:'.$currentYear],
            'zones.*.is_active' => ['bail', 'nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $zones = $this->input('zones');

        if (! is_array($zones)) {
            return;
        }

        $cleaned = [];
        foreach ($zones as $zone) {
            $cleaned[] = [
                'name' => is_string($zone['name'] ?? null) ? trim(strip_tags($zone['name'])) : ($zone['name'] ?? null),
                'description' => is_string($zone['description'] ?? null) ? trim(strip_tags($zone['description'])) : ($zone['description'] ?? null),
                'established_year' => $zone['established_year'] ?? null,
                'is_active' => array_key_exists('is_active', $zone) ? $zone['is_active'] : true,
            ];
        }

        $this->merge(['zones' => $cleaned]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $zones = $this->input('zones');

            if (! is_array($zones)) {
                return;
            }

            $names = [];
            $rawNames = [];
            foreach ($zones as $i => $zone) {
                $name = $zone['name'] ?? null;
                if (! is_string($name) || trim($name) === '') {
                    continue;
                }

                $trimmed = trim($name);
                $key = mb_strtolower($trimmed);
                if (isset($names[$key])) {
                    $v->errors()->add("zones.$i.name", 'Duplicate zone name in this request.');
                }

                $names[$key] = true;
                $rawNames[] = $trimmed;
            }

            if (empty($names)) {
                return;
            }

            $parishId = Parish::query()->value('id');
            if (! $parishId) {
                return;
            }

            $existing = Zone::query()
                ->where('parish_id', $parishId)
                ->whereIn('name', $rawNames)
                ->pluck('name')
                ->map(fn ($n) => mb_strtolower(trim((string) $n)))
                ->unique()
                ->values()
                ->all();

            if (! empty($existing)) {
                foreach ($zones as $i => $zone) {
                    $name = $zone['name'] ?? null;
                    if (! is_string($name) || trim($name) === '') {
                        continue;
                    }

                    if (in_array(mb_strtolower(trim($name)), $existing, true)) {
                        $v->errors()->add("zones.$i.name", 'Zone name already exists.');
                    }
                }
            }
        });
    }
}
