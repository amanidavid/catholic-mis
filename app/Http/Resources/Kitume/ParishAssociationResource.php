<?php

namespace App\Http\Resources\Kitume;

use App\Models\Kitume\ParishAssociationLeadership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParishAssociationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'active_members_count' => (int) ($this->active_members_count ?? 0),
            'active_leaders_count' => (int) ($this->active_leaders_count ?? 0),
            'leaders' => $this->whenLoaded('leaderships', fn () => $this->leaderships->map(function (ParishAssociationLeadership $leadership) {
                return [
                    'uuid' => $leadership->uuid,
                    'role_name' => $leadership->role?->name,
                    'member_name' => trim(implode(' ', array_filter([
                        $leadership->member?->first_name,
                        $leadership->member?->middle_name,
                        $leadership->member?->last_name,
                    ]))),
                ];
            })->values(), []),
        ];
    }
}
