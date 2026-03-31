<?php

namespace App\Http\Resources\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PettyCashVoucherLineResource extends JsonResource
{
    use FormatsAmounts;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'expense_ledger_uuid' => $this->expenseLedger?->uuid,
            'expense_ledger_name' => $this->expenseLedger?->name,
            'expense_ledger_account_code' => $this->expenseLedger?->account_code,
            'description' => $this->description,
            'amount' => self::normalizeAmount($this->amount, 4),
            'amount_formatted' => self::formatAmount($this->amount, 2),
        ];
    }
}
