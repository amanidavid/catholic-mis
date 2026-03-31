<?php

namespace App\Http\Resources\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PettyCashBookEntryResource extends JsonResource
{
    use FormatsAmounts;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'description' => $this->description,
            'fund_uuid' => $this->fund_uuid,
            'fund_name' => $this->fund_name,
            'fund_code' => $this->fund_code,
            'journal_no' => $this->journal_no,
            'voucher_no' => $this->voucher_no,
            'replenishment_no' => $this->replenishment_no,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'debit_amount' => self::normalizeAmount($this->debit_amount, 4),
            'debit_amount_formatted' => self::formatAmount($this->debit_amount, 2),
            'credit_amount' => self::normalizeAmount($this->credit_amount, 4),
            'credit_amount_formatted' => self::formatAmount($this->credit_amount, 2),
        ];
    }
}
