<?php

namespace App\Http\Resources\Finance\Banking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankTransactionMappingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ledger = $this->ledger;
        $debitLedger = $this->debitLedger;
        $creditLedger = $this->creditLedger;

        return [
            'uuid' => $this->uuid,
            'transaction_type' => $this->transaction_type,
            'ledger_uuid' => $ledger?->uuid,
            'debit_ledger_uuid' => $debitLedger?->uuid,
            'debit_ledger_name' => $debitLedger?->name,
            'debit_ledger_account_code' => $debitLedger?->account_code,
            'credit_ledger_uuid' => $creditLedger?->uuid,
            'credit_ledger_name' => $creditLedger?->name,
            'credit_ledger_account_code' => $creditLedger?->account_code,
        ];
    }
}
