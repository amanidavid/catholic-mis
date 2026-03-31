<?php

namespace App\Http\Resources\Finance\Banking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'is_active' => (bool) ($this->is_active ?? false),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
