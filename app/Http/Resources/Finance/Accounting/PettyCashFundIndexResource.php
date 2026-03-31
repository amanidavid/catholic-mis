<?php

namespace App\Http\Resources\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PettyCashFundIndexResource extends JsonResource
{
    use FormatsAmounts;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'ledger_uuid' => $this->ledger_uuid ?? $this->ledger?->uuid,
            'ledger_name' => $this->ledger_name ?? $this->ledger?->name,
            'currency_uuid' => $this->currency_uuid ?? $this->currency?->uuid,
            'currency_code' => $this->currency_code ?? $this->currency?->code,
            'custodian_name' => $this->custodian_name ?? $this->custodian?->name,
            'imprest_amount' => self::normalizeAmount($this->imprest_amount, 4),
            'imprest_amount_formatted' => self::formatAmount($this->imprest_amount, 2),
            'min_reorder_amount' => self::normalizeAmount($this->min_reorder_amount, 4),
            'min_reorder_amount_formatted' => $this->min_reorder_amount === null
                ? null
                : self::formatAmount($this->min_reorder_amount, 2),
            'gl_balance_signed' => self::normalizeAmount($this->gl_balance_signed ?? 0, 4),
            'gl_balance_formatted' => self::formatAmount(abs((float) ($this->gl_balance_signed ?? 0)), 2),
            'gl_balance_side' => (float) ($this->gl_balance_signed ?? 0) < 0 ? 'Cr' : 'Dr',
            'needs_replenishment' => $this->min_reorder_amount !== null
                && (float) ($this->gl_balance_signed ?? 0) <= (float) $this->min_reorder_amount,
            'is_active' => (bool) ($this->is_active ?? false),
        ];
    }
}
