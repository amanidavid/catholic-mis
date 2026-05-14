<?php

namespace App\Http\Resources\Pastoral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeceasedRegisterEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'member_uuid' => $this->member?->uuid,
            'member_name' => $this->member
                ? trim(implode(' ', array_filter([
                    $this->member->first_name,
                    $this->member->middle_name,
                    $this->member->last_name,
                ])))
                : null,
            'family_uuid' => $this->member?->family?->uuid,
            'family_name' => $this->member?->family?->family_name,
            'jumuiya_uuid' => $this->member?->jumuiya?->uuid,
            'jumuiya_name' => $this->member?->jumuiya?->name,
            'date_of_death' => optional($this->date_of_death)?->format('Y-m-d'),
            'time_of_death' => $this->time_of_death,
            'place_of_death' => $this->place_of_death,
            'cause_of_death' => $this->cause_of_death,
            'death_certificate_number' => $this->death_certificate_number,
            'hospital_or_health_facility' => $this->hospital_or_health_facility,
            'funeral_date' => optional($this->funeral_date)?->format('Y-m-d'),
            'burial_date' => optional($this->burial_date)?->format('Y-m-d'),
            'burial_location_or_cemetery' => $this->burial_location_or_cemetery,
            'funeral_mass_location' => $this->funeral_mass_location,
            'priest_or_celebrant_name' => $this->priest_or_celebrant_name,
            'homily_or_remarks' => $this->homily_or_remarks,
            'notes' => $this->notes,
            'recorded_by_user_name' => $this->recordedByUser?->name,
            'created_at' => optional($this->created_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
