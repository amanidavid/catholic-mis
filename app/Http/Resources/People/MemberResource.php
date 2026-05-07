<?php

namespace App\Http\Resources\People;

use App\Models\People\MemberSacramentStatus;
use App\Support\MemberMaritalStatuses;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isHead = false;
        if ($this->relationLoaded('family') && $this->family) {
            $isHead = (int) ($this->family->head_of_family_member_id ?? 0) === (int) $this->id;
        }

        $statusRows = $this->relationLoaded('sacramentStatuses')
            ? $this->sacramentStatuses->keyBy('sacrament_type')
            : collect();

        $mapStatus = function (string $type) use ($statusRows): array {
            /** @var MemberSacramentStatus|null $row */
            $row = $statusRows->get($type);
            $sourceType = (string) ($row?->source_type ?? '');

            return [
                'is_received' => (bool) ($row?->is_received ?? false),
                'certificate_no' => $row?->certificate_no,
                'sacrament_date' => $row?->sacrament_date?->format('Y-m-d'),
                'source_type' => $sourceType !== '' ? $sourceType : null,
                'source_record_uuid' => $row?->source_record_uuid,
                'source_label' => match ($sourceType) {
                    MemberSacramentStatus::SOURCE_BAPTISM => 'Linked baptism record',
                    MemberSacramentStatus::SOURCE_PROGRAM_REGISTRATION => 'Linked sacrament registration',
                    MemberSacramentStatus::SOURCE_MANUAL => 'Manual member profile entry',
                    default => null,
                },
            ];
        };

        return [
            'uuid' => $this->uuid,
            'outstation_uuid' => $this->jumuiya?->zone?->outstation?->uuid,
            'outstation_name' => $this->jumuiya?->zone?->outstation?->name,
            'zone_uuid' => $this->jumuiya?->zone?->uuid,
            'zone_name' => $this->jumuiya?->zone?->name,
            'jumuiya_uuid' => $this->jumuiya?->uuid,
            'jumuiya_name' => $this->jumuiya?->name,
            'family_uuid' => $this->family?->uuid,
            'family_name' => $this->family?->family_name,
            'family_relationship_uuid' => $this->familyRelationship?->uuid,
            'family_relationship_name' => $this->familyRelationship?->name,
            'is_head_of_family' => $isHead,
            'system_roles' => $this->relationLoaded('user') && $this->user && $this->user->relationLoaded('roles')
                ? $this->user->roles->pluck('name')->values()->all()
                : [],
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => trim(implode(' ', array_filter([
                $this->first_name,
                $this->middle_name,
                $this->last_name,
            ]))),
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'phone' => $this->phone,
            'email' => $this->email,
            'national_id' => $this->national_id,
            'marital_status' => $this->marital_status,
            'marital_status_label' => MemberMaritalStatuses::label($this->marital_status),
            'is_active' => (bool) $this->is_active,
            'sacrament_statuses' => [
                'baptism' => $mapStatus(MemberSacramentStatus::TYPE_BAPTISM),
                'communion' => $mapStatus(MemberSacramentStatus::TYPE_COMMUNION),
                'confirmation' => $mapStatus(MemberSacramentStatus::TYPE_CONFIRMATION),
            ],
        ];
    }
}
