<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContributionRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'catalog_uuid' => $this->catalog?->uuid,
            'catalog_name' => $this->catalog?->name,
            'catalog_code' => $this->catalog?->code,
            'amount' => (float) $this->amount,
            'currency_code' => $this->currency_code,
            'is_required' => (bool) ($this->is_required ?? true),
            'allow_partial_payment' => (bool) ($this->allow_partial_payment ?? false),
            'allow_override' => (bool) ($this->allow_override ?? false),
            'waiver_allowed' => (bool) ($this->waiver_allowed ?? false),
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
            'sort_order' => (int) ($this->sort_order ?? 0),
            'is_active' => (bool) ($this->is_active ?? true),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
