<?php

namespace App\Http\Resources\Structure;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DioceseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'archbishop_name' => $this->archbishop_name,
            'established_year' => $this->established_year,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'country' => $this->country,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
