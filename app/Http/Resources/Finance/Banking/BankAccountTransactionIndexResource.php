<?php

namespace App\Http\Resources\Finance\Banking;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountTransactionIndexResource extends JsonResource
{
    use FormatsAmounts;

    public function toArray(Request $request): array
    {
        $account = $this->bankAccount;
        $bank = $account?->bank;
        $currency = $account?->currency;
        $journal = $this->journal;
        $debitLedger = $this->debitLedger;
        $creditLedger = $this->creditLedger;

        return [
            'uuid' => $this->uuid,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'transaction_type' => $this->transaction_type,
            'direction' => $this->direction,
            'amount' => self::normalizeAmount($this->amount, 4),
            'amount_formatted' => self::formatAmount($this->amount, 2),
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'journal_uuid' => $journal?->uuid,
            'journal_no' => $journal?->journal_no,
            'double_entry_uuid' => $this->doubleEntry?->uuid,
            'is_manual_override' => (bool) ($this->is_manual_override ?? false),
            'debit_ledger_uuid' => $debitLedger?->uuid,
            'debit_ledger_name' => $debitLedger?->name,
            'debit_ledger_account_code' => $debitLedger?->account_code,
            'credit_ledger_uuid' => $creditLedger?->uuid,
            'credit_ledger_name' => $creditLedger?->name,
            'credit_ledger_account_code' => $creditLedger?->account_code,
            'bank_account_uuid' => $account?->uuid,
            'bank_account_name' => $account?->account_name,
            'bank_account_number' => $account?->account_number,
            'bank_account_number_masked' => $this->maskAccountNumber($account?->account_number),
            'bank_name' => $bank?->name,
            'currency_code' => $currency?->code,
        ];
    }

    private function maskAccountNumber(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        $length = strlen($trimmed);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', max(0, $length - 4)) . substr($trimmed, -4);
    }
}
