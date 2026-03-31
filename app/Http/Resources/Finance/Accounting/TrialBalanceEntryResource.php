<?php

namespace App\Http\Resources\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrialBalanceEntryResource extends JsonResource
{
    use FormatsAmounts;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $naturalClass = (string) ($this->natural_class ?? 'Other');
        $balanceSide = (string) ($this->natural_balance_side ?? 'Debit');

        return [
            'ledger_uuid' => $this->ledger_uuid,
            'ledger_name' => $this->ledger_name,
            'natural_class' => $naturalClass,
            'natural_balance_side' => $balanceSide,
            'balance_type' => "{$naturalClass} / {$balanceSide}",
            'debit_balance' => self::normalizeAmount($this->debit_balance ?? 0, 4),
            'debit_balance_formatted' => self::formatAmount($this->debit_balance ?? 0, 2),
            'credit_balance' => self::normalizeAmount($this->credit_balance ?? 0, 4),
            'credit_balance_formatted' => self::formatAmount($this->credit_balance ?? 0, 2),
        ];
    }
}
