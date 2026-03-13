<?php

namespace App\Http\Requests\Jumuiya;

use App\Models\Structure\Jumuiya;
use App\Models\Structure\Zone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJumuiyasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('jumuiyas.create') ?? false;
    }

    public function rules(): array
    {
        $currentYear = (int) now()->year;

        return [
            'zone_uuid' => ['required', 'uuid', Rule::exists(Zone::class, 'uuid')],
            'jumuiyas' => ['required', 'array', 'min:1', 'max:50'],
            'jumuiyas.*.name' => ['required', 'string', 'max:120'],
            'jumuiyas.*.meeting_day' => ['nullable', 'string', 'max:30'],
            'jumuiyas.*.established_year' => ['nullable', 'integer', 'min:1800', 'max:'.$currentYear],
            'jumuiyas.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $zoneUuid = is_string($this->zone_uuid) ? trim($this->zone_uuid) : $this->zone_uuid;

        $jumuiyas = $this->input('jumuiyas');
        if (! is_array($jumuiyas)) {
            $jumuiyas = [];
        }

        $clean = [];
        foreach ($jumuiyas as $row) {
            if (! is_array($row)) {
                continue;
            }

            $clean[] = [
                'name' => isset($row['name']) && is_string($row['name']) ? trim($row['name']) : '',
                'meeting_day' => isset($row['meeting_day']) && is_string($row['meeting_day']) ? trim($row['meeting_day']) : null,
                'established_year' => $row['established_year'] ?? null,
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
            ];
        }

        $this->merge([
            'zone_uuid' => $zoneUuid,
            'jumuiyas' => $clean,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $payload = $this->validated();
            $zoneUuid = $payload['zone_uuid'] ?? null;
            $rows = $payload['jumuiyas'] ?? [];

            if (! is_string($zoneUuid) || $zoneUuid === '' || ! is_array($rows)) {
                return;
            }

            $names = [];
            foreach ($rows as $idx => $row) {
                $name = is_string($row['name'] ?? null) ? $row['name'] : '';
                $key = mb_strtolower($name);
                if ($key === '') {
                    continue;
                }

                if (array_key_exists($key, $names)) {
                    $validator->errors()->add("jumuiyas.$idx.name", 'Duplicate name in request.');
                } else {
                    $names[$key] = true;
                }
            }

            $zone = Zone::query()->where('uuid', $zoneUuid)->first();
            if (! $zone) {
                return;
            }

            $existing = Jumuiya::query()
                ->where('zone_id', $zone->id)
                ->pluck('name')
                ->map(fn ($v) => mb_strtolower((string) $v))
                ->all();

            $existingMap = array_fill_keys($existing, true);

            foreach ($rows as $idx => $row) {
                $name = is_string($row['name'] ?? null) ? $row['name'] : '';
                $key = mb_strtolower($name);
                if ($key !== '' && array_key_exists($key, $existingMap)) {
                    $validator->errors()->add("jumuiyas.$idx.name", 'A Jumuiya with this name already exists in the selected zone.');
                }
            }
        });
    }
}
