<?php

namespace App\Http\Resources\Kitume;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParishAssociationMemberResource extends JsonResource
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
            'gender' => $this->member?->gender,
            'phone' => $this->member?->phone,
            'email' => $this->member?->email,
            'outstation_name' => $this->member?->jumuiya?->zone?->outstation?->name,
            'zone_name' => $this->member?->jumuiya?->zone?->name,
            'jumuiya_name' => $this->member?->jumuiya?->name,
            'member_is_active' => (bool) ($this->member?->is_active ?? false),
            'joined_at' => $this->joined_at?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
