<?php

namespace App\Http\Resources\Sacraments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class SacramentProgramRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $member = $this->whenLoaded('member');
        $family = $this->whenLoaded('family');
        $originJumuiya = $this->whenLoaded('originJumuiya');
        $cycle = $this->whenLoaded('cycle');
        $attachments = $this->whenLoaded('attachments');

        if ($member instanceof MissingValue) {
            $member = null;
        }
        if ($family instanceof MissingValue) {
            $family = null;
        }
        if ($originJumuiya instanceof MissingValue) {
            $originJumuiya = null;
        }
        if ($cycle instanceof MissingValue) {
            $cycle = null;
        }

        return [
            'id' => (int) $this->id,
            'uuid' => (string) $this->uuid,
            'program' => (string) ($this->program ?? ''),
            'status' => (string) ($this->status ?? ''),
            'is_transfer' => (bool) ($this->is_transfer ?? false),

            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'issued_at' => $this->issued_at?->format('Y-m-d H:i'),

            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,

            'member' => $member
                ? [
                    'id' => (int) $member->id,
                    'uuid' => (string) $member->uuid,
                    'first_name' => (string) $member->first_name,
                    'middle_name' => $member->middle_name,
                    'last_name' => (string) $member->last_name,
                    'full_name' => trim(implode(' ', array_filter([$member->first_name, $member->middle_name, $member->last_name]))),
                ]
                : null,

            'family' => $family
                ? [
                    'id' => (int) $family->id,
                    'uuid' => (string) $family->uuid,
                    'family_name' => (string) ($family->family_name ?? ''),
                ]
                : null,

            'origin_jumuiya' => $originJumuiya
                ? [
                    'id' => (int) $originJumuiya->id,
                    'uuid' => (string) $originJumuiya->uuid,
                    'name' => (string) ($originJumuiya->name ?? ''),
                ]
                : null,

            'cycle' => $cycle ? (new SacramentProgramCycleResource($cycle))->toArray($request) : null,

            'attachments' => ($attachments instanceof MissingValue)
                ? []
                : (is_iterable($attachments)
                    ? collect($attachments)->map(fn ($a) => [
                        'id' => (int) $a->id,
                        'uuid' => (string) $a->uuid,
                        'type' => (string) $a->type,
                        'original_name' => (string) $a->original_name,
                        'mime_type' => (string) $a->mime_type,
                        'size_bytes' => (int) $a->size_bytes,
                        'created_at' => $a->created_at?->format('Y-m-d H:i'),
                    ])->values()->all()
                    : []),
        ];
    }
}
