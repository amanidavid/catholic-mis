<?php

namespace App\Http\Resources\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralLedgerEntryResource extends JsonResource
{
    use FormatsAmounts;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $journal = $this->journal;
        $ledger = $this->ledger;

        return [
            'uuid' => $this->uuid,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'description' => $this->description,
            'debit_amount' => self::normalizeAmount($this->debit_amount, 4),
            'debit_amount_formatted' => self::formatAmount($this->debit_amount, 2),
            'credit_amount' => self::normalizeAmount($this->credit_amount, 4),
            'credit_amount_formatted' => self::formatAmount($this->credit_amount, 2),
            'ledger_uuid' => $ledger?->uuid,
            'ledger_name' => $ledger?->name,
            'ledger_account_code' => $ledger?->account_code,
            'journal_uuid' => $journal?->uuid,
            'journal_no' => $journal?->journal_no,
        ];
    }
}
