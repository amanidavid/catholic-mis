<?php

namespace App\Http\Resources\Finance\Banking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountIndexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bankUuid = $this->bank_uuid ?? ($this->bank->uuid ?? null);
        $bankName = $this->bank_name ?? ($this->bank->name ?? null);
        $ledgerUuid = $this->ledger_uuid ?? ($this->ledger->uuid ?? null);
        $ledgerName = $this->ledger_name ?? ($this->ledger->name ?? null);
        $ledgerAccountCode = $this->ledger_account_code ?? ($this->ledger->account_code ?? null);
        $currencyUuid = $this->currency_uuid ?? ($this->currency->uuid ?? null);
        $currencyCode = $this->currency_code ?? ($this->currency->code ?? null);
        $currencyName = $this->currency_name ?? ($this->currency->name ?? null);

        return [
            'uuid' => $this->uuid,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'account_number_masked' => $this->maskAccountNumber($this->account_number),
            'branch' => $this->branch,
            'swift_code' => $this->swift_code,
            'is_active' => (bool) ($this->is_active ?? false),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'bank_uuid' => $bankUuid,
            'bank_name' => $bankName,
            'ledger_uuid' => $ledgerUuid,
            'ledger_name' => $ledgerName,
            'ledger_account_code' => $ledgerAccountCode,
            'currency_uuid' => $currencyUuid,
            'currency_code' => $currencyCode,
            'currency_name' => $currencyName,
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
