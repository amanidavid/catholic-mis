<?php

namespace App\Http\Resources\Sacraments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaptismResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $member = $this->whenLoaded('member');
        $originJumuiya = $this->whenLoaded('originJumuiya');
        $family = $this->whenLoaded('family');
        $attachments = $this->whenLoaded('attachments');

        $zone = null;
        $parish = null;

        if ($originJumuiya && $originJumuiya->relationLoaded('zone')) {
            $zone = $originJumuiya->zone;
            if ($zone && $zone->relationLoaded('parish')) {
                $parish = $zone->parish;
            }
        }

        if (! $parish && $this->relationLoaded('parish')) {
            $parish = $this->parish;
        }

        return [
            'id' => (int) $this->id,
            'uuid' => (string) $this->uuid,

            'status' => (string) ($this->status ?? ''),
            'rejection_reason' => $this->rejection_reason,

            'certificate_no' => $this->certificate_no,

            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'baptism_date' => $this->baptism_date?->format('Y-m-d'),

            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i'),
            'issued_at' => $this->issued_at?->format('Y-m-d H:i'),

            'parish' => $parish
                ? [
                    'id' => (int) $parish->id,
                    'uuid' => (string) $parish->uuid,
                    'name' => (string) $parish->name,
                    'code' => $parish->code,
                ]
                : null,

            'zone' => $zone
                ? [
                    'id' => (int) $zone->id,
                    'uuid' => (string) $zone->uuid,
                    'name' => (string) $zone->name,
                ]
                : null,

            'origin_jumuiya' => $originJumuiya
                ? [
                    'id' => (int) $originJumuiya->id,
                    'uuid' => (string) $originJumuiya->uuid,
                    'name' => (string) $originJumuiya->name,
                ]
                : null,

            'family' => $family
                ? [
                    'id' => (int) $family->id,
                    'uuid' => (string) $family->uuid,
                    'family_name' => (string) $family->family_name,
                ]
                : null,

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

            'father' => $this->whenLoaded('fatherMember', function () {
                $f = $this->fatherMember;
                if (! $f) return null;
                return [
                    'id' => (int) $f->id,
                    'uuid' => (string) $f->uuid,
                    'full_name' => trim(implode(' ', array_filter([$f->first_name, $f->middle_name, $f->last_name]))),
                    'marital_status' => $f->marital_status,
                    'phone' => $f->phone,
                    'email' => $f->email,
                ];
            }),

            'mother' => $this->whenLoaded('motherMember', function () {
                $m = $this->motherMember;
                if (! $m) return null;
                return [
                    'id' => (int) $m->id,
                    'uuid' => (string) $m->uuid,
                    'full_name' => trim(implode(' ', array_filter([$m->first_name, $m->middle_name, $m->last_name]))),
                    'marital_status' => $m->marital_status,
                    'phone' => $m->phone,
                    'email' => $m->email,
                ];
            }),

            'issued_by' => $this->whenLoaded('issuedBy', function () {
                $u = $this->issuedBy;
                if (! $u) return null;
                return [
                    'id' => (int) $u->id,
                    'uuid' => (string) $u->uuid,
                    'name' => (string) $u->name,
                    'email' => (string) $u->email,
                ];
            }),

            'sponsors' => $this->whenLoaded('sponsors', function () {
                return $this->sponsors->map(function ($s) {
                    return [
                        'id' => (int) $s->id,
                        'uuid' => (string) $s->uuid,
                        'role' => $s->role,
                        'full_name' => $s->full_name,
                        'parish_name' => $s->parish_name,
                        'phone' => $s->phone,
                        'email' => $s->email,
                        'member' => $s->relationLoaded('member') && $s->member
                            ? [
                                'id' => (int) $s->member->id,
                                'uuid' => (string) $s->member->uuid,
                                'first_name' => (string) $s->member->first_name,
                                'middle_name' => $s->member->middle_name,
                                'last_name' => (string) $s->member->last_name,
                                'phone' => $s->member->phone,
                                'email' => $s->member->email,
                            ]
                            : null,
                    ];
                })->values();
            }),

            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(fn ($a) => [
                    'id' => (int) $a->id,
                    'uuid' => (string) $a->uuid,
                    'type' => (string) $a->type,
                    'original_name' => (string) $a->original_name,
                    'created_at' => $a->created_at?->format('Y-m-d H:i'),
                ])->values();
            }, []),
        ];
    }
}
