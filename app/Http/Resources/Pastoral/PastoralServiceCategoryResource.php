<?php

namespace App\Http\Resources\Pastoral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PastoralServiceCategoryResource extends JsonResource
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
        ];
    }
}
