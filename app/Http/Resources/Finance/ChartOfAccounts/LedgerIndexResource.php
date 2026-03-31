<?php

namespace App\Http\Resources\Finance\ChartOfAccounts;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LedgerIndexResource extends JsonResource
{
    use FormatsAmounts;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtype = $this->subtype;
        $type = $subtype?->type;
        $group = $type?->group;
        $currency = $this->currency;

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'account_code' => $this->account_code,
            'opening_balance' => self::normalizeAmount($this->opening_balance, 4),
            'opening_balance_formatted' => self::formatAmount($this->opening_balance, 2),
            'opening_balance_type' => $this->opening_balance_type,
            'opening_balance_signed' => self::signedOpeningBalance($this->opening_balance, $this->opening_balance_type, 4),
            'is_active' => (bool) ($this->is_active ?? false),
            'created_at' => $this->created_at,

            'subtype_uuid' => $subtype?->uuid,
            'subtype_name' => $subtype?->name,
            'type_uuid' => $type?->uuid,
            'type_name' => $type?->name,
            'group_uuid' => $group?->uuid,
            'group_name' => $group?->name,
            'group_code' => $group?->code,
            'currency_uuid' => $currency?->uuid,
            'currency_code' => $currency?->code,
            'currency_name' => $currency?->name,
        ];
    }
}
