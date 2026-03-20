<?php

namespace App\Http\Resources\Sacraments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SacramentProgramCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'uuid' => (string) $this->uuid,
            'program' => (string) ($this->program ?? ''),
            'name' => (string) ($this->name ?? ''),
            'status' => (string) ($this->status ?? ''),
            'registration_opens_at' => $this->registration_opens_at?->format('Y-m-d H:i'),
            'registration_closes_at' => $this->registration_closes_at?->format('Y-m-d H:i'),
            'late_registration_closes_at' => $this->late_registration_closes_at?->format('Y-m-d H:i'),
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
