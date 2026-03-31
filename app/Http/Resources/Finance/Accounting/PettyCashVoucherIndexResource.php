<?php

namespace App\Http\Resources\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PettyCashVoucherIndexResource extends JsonResource
{
    use FormatsAmounts;

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'voucher_no' => $this->voucher_no,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'fund_uuid' => $this->fund?->uuid,
            'fund_name' => $this->fund?->name,
            'payee_name' => $this->payee_name,
            'reference_no' => $this->reference_no,
            'description' => $this->description,
            'amount' => self::normalizeAmount($this->amount, 4),
            'amount_formatted' => self::formatAmount($this->amount, 2),
            'status' => $this->status,
            'journal_uuid' => $this->journal?->uuid,
            'journal_no' => $this->journal?->journal_no,
            'created_by_name' => $this->creator?->name,
            'approved_by_name' => $this->approver?->name,
            'rejected_by_name' => $this->rejector?->name,
            'posted_by_name' => $this->poster?->name,
            'cancelled_by_name' => $this->canceller?->name,
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->rejection_reason,
            'posted_at' => $this->posted_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'attachments' => $this->relationLoaded('attachments')
                ? $this->attachments->map(fn ($attachment) => [
                    'uuid' => $attachment->uuid,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'size_bytes' => (int) ($attachment->size_bytes ?? 0),
                    'created_at' => $attachment->created_at?->format('Y-m-d H:i:s'),
                ])->values()->all()
                : [],
            'lines' => $this->relationLoaded('lines')
                ? PettyCashVoucherLineResource::collection($this->lines)->resolve()
                : [],
        ];
    }
}
