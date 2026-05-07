<?php

namespace App\Http\Resources\Kitume;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParishAssociationReportRowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => (string) $this->uuid,
            'name' => (string) $this->name,
            'code' => $this->code ? (string) $this->code : null,
            'description' => $this->description ? (string) $this->description : null,
            'is_active' => (bool) $this->is_active,
            'total_members' => (int) $this->total_members,
            'total_leaders' => (int) $this->total_leaders,
            'men' => (int) $this->men,
            'women' => (int) $this->women,
            'outstations' => (int) ($this->outstations ?? 0),
        ];
    }
}
