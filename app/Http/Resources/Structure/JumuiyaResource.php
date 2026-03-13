<?php

namespace App\Http\Resources\Structure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JumuiyaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'zone_uuid' => optional($this->zone)->uuid,
            'zone_name' => optional($this->zone)->name,
            'name' => $this->name,
            'meeting_day' => $this->meeting_day,
            'established_year' => $this->established_year,
            'is_active' => (bool) $this->is_active,
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}
