<?php

namespace App\Http\Resources\Finance\Accounting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ledger = $this->ledger;

        return [
            'uuid' => $this->uuid,
            'ledger_uuid' => $ledger?->uuid,
            'ledger_name' => $ledger?->name,
            'ledger_account_code' => $ledger?->account_code,
            'description' => $this->description,
            'debit_amount' => $this->debit_amount,
            'credit_amount' => $this->credit_amount,
        ];
    }
}
