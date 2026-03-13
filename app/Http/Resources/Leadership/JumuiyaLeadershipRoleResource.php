<?php

namespace App\Http\Resources\Leadership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JumuiyaLeadershipRoleResource extends JsonResource
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
            'name' => $this->name,
            'system_role_name' => $this->system_role_name,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
