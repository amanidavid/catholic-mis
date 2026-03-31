<?php

namespace App\Http\Resources\Finance\Accounting;

use App\Traits\FormatsAmounts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalShowResource extends JsonResource
{
    use FormatsAmounts;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'journal_no' => $this->journal_no,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'description' => $this->description,
            'amount' => self::normalizeAmount($this->amount, 4),
            'amount_formatted' => self::formatAmount($this->amount, 2),
            'is_posted' => (bool) ($this->is_posted ?? false),
            'posted_at' => $this->posted_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'lines' => JournalLineResource::collection($this->whenLoaded('lines'))->resolve(),
        ];
    }
}
