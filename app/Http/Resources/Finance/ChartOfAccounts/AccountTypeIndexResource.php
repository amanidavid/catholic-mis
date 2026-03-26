<?php

namespace App\Http\Resources\Finance\ChartOfAccounts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountTypeIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $groupUuid = $this->group_uuid ?? ($this->group->uuid ?? null);
        $groupName = $this->group_name ?? ($this->group->name ?? null);
        $groupCode = $this->group_code ?? ($this->group->code ?? null);

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'is_active' => (bool) ($this->is_active ?? false),
            'created_at' => $this->created_at,
            'group_uuid' => $groupUuid,
            'group_name' => $groupName,
            'group_code' => $groupCode,
        ];
    }
}
