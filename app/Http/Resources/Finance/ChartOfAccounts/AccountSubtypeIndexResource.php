<?php

namespace App\Http\Resources\Finance\ChartOfAccounts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountSubtypeIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'is_active' => (bool) ($this->is_active ?? false),
            'created_at' => $this->created_at,
            'type_uuid' => $this->type_uuid,
            'type_name' => $this->type_name,
            'group_uuid' => $this->group_uuid,
            'group_name' => $this->group_name,
            'group_code' => $this->group_code,
        ];
    }
}
