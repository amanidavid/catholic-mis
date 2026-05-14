<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContributionPaymentRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'catalog_uuid' => $this->catalog?->uuid,
            'catalog_name' => $this->catalog?->name,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'subject_member_uuid' => $this->subjectMember?->uuid,
            'subject_member_name' => $this->subjectMember?->first_name . ' ' . $this->subjectMember?->last_name,
            'payer_member_uuid' => $this->payerMember?->uuid,
            'payer_member_name' => $this->payerMember?->first_name . ' ' . $this->payerMember?->last_name,
            'family_uuid' => $this->family?->uuid,
            'family_name' => $this->family?->family_name,
            'rule_snapshot_name' => $this->rule_snapshot_name,
            'rule_snapshot_code' => $this->rule_snapshot_code,
            'rule_snapshot_amount' => (float) $this->rule_snapshot_amount,
            'currency_code' => $this->currency_code,
            'amount_due' => (float) $this->amount_due,
            'amount_paid' => (float) $this->amount_paid,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
