<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContributionTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'obligation_uuid' => $this->obligation?->uuid,
            'member_uuid' => $this->member?->uuid,
            'member_name' => $this->member?->first_name . ' ' . $this->member?->last_name,
            'transaction_type' => $this->transaction_type,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'reference_no' => $this->reference_no,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
