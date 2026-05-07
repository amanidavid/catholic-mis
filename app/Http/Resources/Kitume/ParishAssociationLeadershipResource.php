<?php

namespace App\Http\Resources\Kitume;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParishAssociationLeadershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'member_uuid' => $this->member?->uuid,
            'member_name' => trim(implode(' ', array_filter([
                $this->member?->first_name,
                $this->member?->middle_name,
                $this->member?->last_name,
            ]))),
            'email' => $this->member?->email,
            'phone' => $this->member?->phone,
            'outstation_name' => $this->member?->jumuiya?->zone?->outstation?->name,
            'zone_name' => $this->member?->jumuiya?->zone?->name,
            'jumuiya_name' => $this->member?->jumuiya?->name,
            'role_uuid' => $this->role?->uuid,
            'role_name' => $this->role?->name,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_active' => (bool) $this->is_active,
        ];
    }
}
