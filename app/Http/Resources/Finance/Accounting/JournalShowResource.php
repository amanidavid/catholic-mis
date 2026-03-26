<?php

namespace App\Http\Resources\Finance\Accounting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalShowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'journal_no' => $this->journal_no,
            'transaction_date' => $this->transaction_date,
            'description' => $this->description,
            'amount' => $this->amount,
            'is_posted' => (bool) ($this->is_posted ?? false),
            'posted_at' => $this->posted_at,
            'created_at' => $this->created_at,
            'lines' => JournalLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
