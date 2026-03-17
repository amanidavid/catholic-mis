<?php

namespace App\Http\Resources\Sacraments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class MarriageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $groom = $this->whenLoaded('groom');
        $bride = $this->whenLoaded('bride');
        $attachments = $this->whenLoaded('attachments');
        $parents = $this->whenLoaded('parents');
        $sponsors = $this->whenLoaded('sponsors');

        $groomJumuiya = $this->whenLoaded('groomJumuiya');
        if ($groomJumuiya instanceof MissingValue) {
            $groomJumuiya = null;
        }

        $brideJumuiya = $this->whenLoaded('brideJumuiya');
        if ($brideJumuiya instanceof MissingValue) {
            $brideJumuiya = null;
        }

        $groomZone = null;
        $groomParish = null;
        if ($groomJumuiya && $groomJumuiya->relationLoaded('zone')) {
            $groomZone = $groomJumuiya->zone;
            if ($groomZone && $groomZone->relationLoaded('parish')) {
                $groomParish = $groomZone->parish;
            }
        }

        $brideZone = null;
        $brideParish = null;
        if ($brideJumuiya && $brideJumuiya->relationLoaded('zone')) {
            $brideZone = $brideJumuiya->zone;
            if ($brideZone && $brideZone->relationLoaded('parish')) {
                $brideParish = $brideZone->parish;
            }
        }

        return [
            'id' => (int) $this->id,
            'uuid' => (string) $this->uuid,

            'status' => (string) ($this->status ?? ''),
            'rejection_reason' => $this->rejection_reason,

            'marriage_date' => $this->marriage_date?->format('Y-m-d'),
            'marriage_time' => $this->marriage_time,
            'wedding_type' => $this->wedding_type,

            'certificate_no' => $this->certificate_no,

            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'issued_at' => $this->issued_at?->format('Y-m-d H:i'),

            'groom' => $groom
                ? [
                    'id' => (int) $groom->id,
                    'uuid' => (string) $groom->uuid,
                    'first_name' => (string) $groom->first_name,
                    'middle_name' => $groom->middle_name,
                    'last_name' => (string) $groom->last_name,
                    'full_name' => trim(implode(' ', array_filter([$groom->first_name, $groom->middle_name, $groom->last_name]))),
                ]
                : null,

            'bride' => $bride
                ? [
                    'id' => (int) $bride->id,
                    'uuid' => (string) $bride->uuid,
                    'first_name' => (string) $bride->first_name,
                    'middle_name' => $bride->middle_name,
                    'last_name' => (string) $bride->last_name,
                    'full_name' => trim(implode(' ', array_filter([$bride->first_name, $bride->middle_name, $bride->last_name]))),
                ]
                : null,

            'bride_external' => [
                'full_name' => $this->bride_external_full_name,
                'phone' => $this->bride_external_phone,
                'address' => $this->bride_external_address,
                'home_parish_name' => $this->bride_external_home_parish_name,
            ],

            'groom_jumuiya' => $groomJumuiya
                ? [
                    'id' => (int) $groomJumuiya->id,
                    'uuid' => (string) $groomJumuiya->uuid,
                    'name' => (string) $groomJumuiya->name,
                ]
                : null,
            'groom_zone' => $groomZone
                ? [
                    'id' => (int) $groomZone->id,
                    'uuid' => (string) $groomZone->uuid,
                    'name' => (string) $groomZone->name,
                ]
                : null,
            'groom_parish' => $groomParish
                ? [
                    'id' => (int) $groomParish->id,
                    'uuid' => (string) $groomParish->uuid,
                    'name' => (string) $groomParish->name,
                    'code' => $groomParish->code,
                ]
                : null,

            'bride_jumuiya' => $brideJumuiya
                ? [
                    'id' => (int) $brideJumuiya->id,
                    'uuid' => (string) $brideJumuiya->uuid,
                    'name' => (string) $brideJumuiya->name,
                ]
                : null,
            'bride_zone' => $brideZone
                ? [
                    'id' => (int) $brideZone->id,
                    'uuid' => (string) $brideZone->uuid,
                    'name' => (string) $brideZone->name,
                ]
                : null,
            'bride_parish' => $brideParish
                ? [
                    'id' => (int) $brideParish->id,
                    'uuid' => (string) $brideParish->uuid,
                    'name' => (string) $brideParish->name,
                    'code' => $brideParish->code,
                ]
                : null,

            'parents' => ($parents instanceof MissingValue)
                ? []
                : (is_iterable($parents)
                    ? collect($parents)->map(fn ($p) => [
                        'id' => (int) $p->id,
                        'party' => (string) $p->party,
                        'father_name' => $p->father_name,
                        'father_religion' => $p->father_religion,
                        'father_is_alive' => is_null($p->father_is_alive) ? null : (bool) $p->father_is_alive,
                        'mother_name' => $p->mother_name,
                        'mother_religion' => $p->mother_religion,
                        'mother_is_alive' => is_null($p->mother_is_alive) ? null : (bool) $p->mother_is_alive,
                    ])->values()->all()
                    : []),

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

            'witnesses' => [
                'male' => [
                    'member_id' => $this->male_witness_member_id ? (int) $this->male_witness_member_id : null,
                    'name' => $this->male_witness_name,
                    'phone' => $this->male_witness_phone,
                    'address' => $this->male_witness_address,
                    'relationship' => $this->male_witness_relationship,
                ],
                'female' => [
                    'member_id' => $this->female_witness_member_id ? (int) $this->female_witness_member_id : null,
                    'name' => $this->female_witness_name,
                    'phone' => $this->female_witness_phone,
                    'address' => $this->female_witness_address,
                    'relationship' => $this->female_witness_relationship,
                ],
            ],

            'sponsors' => ($sponsors instanceof MissingValue)
                ? []
                : (is_iterable($sponsors)
                    ? collect($sponsors)->map(fn ($s) => [
                        'id' => (int) $s->id,
                        'role' => (string) $s->role,
                        'full_name' => (string) $s->full_name,
                        'phone' => $s->phone,
                        'address' => $s->address,
                        'relationship' => $s->relationship,
                        'notes' => $s->notes,
                    ])->values()->all()
                    : []),
        ];
    }
}
