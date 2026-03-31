<?php

namespace App\Http\Resources\Finance\Banking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bank = $this->bank;
        $currency = $this->currency;
        $ledger = $this->ledger;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'ledger_uuid' => $ledger?->uuid,
            'ledger_name' => $ledger?->name,
            'ledger_account_code' => $ledger?->account_code,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'account_number_masked' => $this->maskAccountNumber($this->account_number),
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
