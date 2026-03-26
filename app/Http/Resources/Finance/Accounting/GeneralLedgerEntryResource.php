<?php

namespace App\Http\Resources\Finance\Accounting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GeneralLedgerEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $journal = $this->journal;

        return [
            'uuid' => $this->uuid,
            'transaction_date' => $this->transaction_date,
            'description' => $this->description,
            'debit_amount' => $this->debit_amount,
            'credit_amount' => $this->credit_amount,
            'journal_uuid' => $journal?->uuid,
            'journal_no' => $journal?->journal_no,
        ];
    }
}
