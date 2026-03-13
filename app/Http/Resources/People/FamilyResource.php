<?php

namespace App\Http\Resources\People;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'zone_uuid' => $this->jumuiya?->zone?->uuid,
            'zone_name' => $this->jumuiya?->zone?->name,
            'jumuiya_uuid' => $this->jumuiya?->uuid,
            'jumuiya_name' => $this->jumuiya?->name,
            'family_name' => $this->family_name,
            'family_code' => $this->family_code,
            'house_number' => $this->house_number,
            'street' => $this->street,
            'head_of_family_member_uuid' => $this->headOfFamily?->uuid,
            'head_of_family_member_name' => $this->headOfFamily
                ? trim(implode(' ', array_filter([
                    $this->headOfFamily->first_name,
                    $this->headOfFamily->middle_name,
                    $this->headOfFamily->last_name,
                ])))
                : null,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
