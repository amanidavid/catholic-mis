<?php

namespace App\Http\Resources\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PettyCashReplenishmentIndexResource extends JsonResource
{
    use FormatsAmounts;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'replenishment_no' => $this->replenishment_no,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'fund_uuid' => $this->fund?->uuid,
            'fund_name' => $this->fund?->name,
            'source_ledger_uuid' => $this->sourceLedger?->uuid,
            'source_ledger_name' => $this->sourceLedger?->name,
            'source_ledger_account_code' => $this->sourceLedger?->account_code,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'amount' => self::normalizeAmount($this->amount, 4),
            'amount_formatted' => self::formatAmount($this->amount, 2),
            'status' => $this->status,
            'journal_no' => $this->journal?->journal_no,
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->rejection_reason,
            'posted_at' => $this->posted_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
        ];
    }
}
