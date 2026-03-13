<?php

namespace App\Http\Requests\Jumuiya;

use App\Models\Structure\Jumuiya;
use App\Models\Structure\Zone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJumuiyaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('jumuiyas.update') ?? false;
    }

    public function rules(): array
    {
        $jumuiya = $this->route('jumuiya');

        $currentYear = (int) now()->year;

        $zoneUuid = $this->input('zone_uuid');
        $targetZoneId = is_string($zoneUuid) && $zoneUuid !== ''
            ? Zone::query()->where('uuid', $zoneUuid)->value('id')
            : $jumuiya?->zone_id;

        return [
            'zone_uuid' => ['required', 'uuid', Rule::exists(Zone::class, 'uuid')],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique(Jumuiya::class, 'name')
                    ->where(fn ($q) => $q->where('zone_id', $targetZoneId))
                    ->ignore($jumuiya?->id),
            ],
            'meeting_day' => ['nullable', 'string', 'max:30'],
            'established_year' => ['nullable', 'integer', 'min:1800', 'max:'.$currentYear],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'zone_uuid' => is_string($this->zone_uuid) ? trim($this->zone_uuid) : $this->zone_uuid,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'meeting_day' => is_string($this->meeting_day) ? trim($this->meeting_day) : $this->meeting_day,
        ]);
    }
}
