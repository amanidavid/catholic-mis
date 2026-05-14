<?php

namespace App\Http\Resources\Pastoral;

use App\Models\Pastoral\PastoralServiceRequestFamily;
use App\Models\Pastoral\PastoralServiceRequestItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PastoralServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'urgency' => $this->urgency,
            'request_date' => optional($this->request_date)?->format('Y-m-d'),
            'preferred_service_date' => optional($this->preferred_service_date)?->format('Y-m-d'),
            'scheduled_service_date' => optional($this->scheduled_service_date)?->format('Y-m-d'),
            'notes' => $this->notes,
            'cancel_reason' => $this->cancel_reason,
            'jumuiya_uuid' => $this->jumuiya?->uuid,
            'jumuiya_name' => $this->jumuiya?->name,
            'assigned_to_user_uuid' => $this->assignedToUser?->uuid,
            'assigned_to_user_name' => $this->assignedToUser?->name,
            'requested_by_member_uuid' => $this->requestedByMember?->uuid,
            'requested_by_member_name' => $this->requestedByMember
                ? trim(implode(' ', array_filter([
                    $this->requestedByMember->first_name,
                    $this->requestedByMember->middle_name,
                    $this->requestedByMember->last_name,
                ])))
                : null,
            'families_count' => (int) ($this->families_count ?? 0),
            'items_count' => (int) ($this->items_count
                ?? ($this->relationLoaded('families')
                    ? $this->families->sum(fn (PastoralServiceRequestFamily $family) => $family->relationLoaded('items') ? $family->items->count() : 0)
                    : 0)),
            'families' => $this->whenLoaded('families', fn () => $this->families->map(function (PastoralServiceRequestFamily $family) {
                return [
                    'uuid' => $family->uuid,
                    'family_uuid' => $family->family?->uuid,
                    'family_name' => $family->family?->family_name,
                    'family_notes' => $family->family_notes,
                    'items' => $family->relationLoaded('items')
                        ? $family->items->map(function (PastoralServiceRequestItem $item) {
                            return [
                                'uuid' => $item->uuid,
                                'service_category_uuid' => $item->category?->uuid,
                                'service_category_name' => $item->category?->name,
                                'target_member_uuid' => $item->targetMember?->uuid,
                                'target_member_name' => $item->targetMember
                                    ? trim(implode(' ', array_filter([
                                        $item->targetMember->first_name,
                                        $item->targetMember->middle_name,
                                        $item->targetMember->last_name,
                                    ])))
                                    : null,
                                'description' => $item->description,
                                'requested_for_date' => optional($item->requested_for_date)?->format('Y-m-d'),
                                'status' => $item->status,
                            ];
                        })->values()->all()
                        : [],
                ];
            })->values()->all(), []),
        ];
    }
}
