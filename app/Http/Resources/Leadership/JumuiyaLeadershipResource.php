<?php

namespace App\Http\Resources\Leadership;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JumuiyaLeadershipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = \App\Models\User::query()->where('member_id', $this->member_id)->first();

        return [
            'uuid' => $this->uuid,
            'jumuiya_uuid' => $this->jumuiya?->uuid,
            'jumuiya_name' => $this->jumuiya?->name,
            'member_uuid' => $this->member?->uuid,
            'member_name' => trim(implode(' ', array_filter([
                $this->member?->first_name,
                $this->member?->middle_name,
                $this->member?->last_name,
            ]))),
            'member_email' => $this->member?->email,
            'role_uuid' => $this->role?->uuid,
            'role_name' => $this->role?->name,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'is_active' => (bool) $this->is_active,
            'has_login' => (bool) ($user?->is_active ?? false),
            'user_is_active' => (bool) ($user?->is_active ?? false),
        ];
    }
}
