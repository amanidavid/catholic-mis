<?php

namespace App\Services\People;

use App\Models\People\Member;
use App\Models\People\MemberSacramentStatus;
use App\Models\Sacraments\Baptism;
use App\Models\Sacraments\SacramentProgramCycle;
use App\Models\Sacraments\SacramentProgramRegistration;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MemberSacramentStatusService
{
    public function syncManualStatuses(Member $member, array $statuses): void
    {
        foreach (MemberSacramentStatus::TYPES as $type) {
            $payload = Arr::get($statuses, $type, []);
            $isReceived = (bool) ($payload['is_received'] ?? false);
            $certificateNo = $this->normalizeNullableString($payload['certificate_no'] ?? null);

            $existing = MemberSacramentStatus::query()
                ->where('member_id', $member->id)
                ->where('sacrament_type', $type)
                ->first();

            if ($existing && ! in_array((string) $existing->source_type, [null, '', MemberSacramentStatus::SOURCE_MANUAL], true)) {
                continue;
            }

            if (! $isReceived && $certificateNo === null) {
                if ($existing && (string) $existing->source_type === MemberSacramentStatus::SOURCE_MANUAL) {
                    $existing->delete();
                }

                continue;
            }

            MemberSacramentStatus::query()->updateOrCreate(
                [
                    'member_id' => $member->id,
                    'sacrament_type' => $type,
                ],
                [
                    'uuid' => $existing?->uuid ?: (string) Str::uuid(),
                    'is_received' => $isReceived,
                    'certificate_no' => $certificateNo,
                    'sacrament_date' => null,
                    'source_type' => MemberSacramentStatus::SOURCE_MANUAL,
                    'source_record_id' => null,
                    'source_record_uuid' => null,
                ]
            );
        }
    }

    public function syncFromBaptism(Baptism $baptism): void
    {
        $memberId = (int) ($baptism->member_id ?? 0);
        if ($memberId <= 0) {
            return;
        }

        $status = (string) ($baptism->status ?? '');
        $isReceived = in_array($status, [Baptism::STATUS_COMPLETED, Baptism::STATUS_ISSUED], true);

        $existing = MemberSacramentStatus::query()
            ->where('member_id', $memberId)
            ->where('sacrament_type', MemberSacramentStatus::TYPE_BAPTISM)
            ->first();

        MemberSacramentStatus::query()->updateOrCreate(
            [
                'member_id' => $memberId,
                'sacrament_type' => MemberSacramentStatus::TYPE_BAPTISM,
            ],
            [
                'uuid' => $existing?->uuid ?: (string) Str::uuid(),
                'is_received' => $isReceived,
                'certificate_no' => $this->normalizeNullableString($baptism->certificate_no)
                    ?? $existing?->certificate_no,
                'sacrament_date' => $isReceived
                    ? ($baptism->baptism_date ?: $baptism->issued_at?->toDateString() ?: $baptism->completed_at?->toDateString())
                    : null,
                'source_type' => MemberSacramentStatus::SOURCE_BAPTISM,
                'source_record_id' => (int) $baptism->id,
                'source_record_uuid' => (string) $baptism->uuid,
            ]
        );
    }

    public function syncFromProgramRegistration(SacramentProgramRegistration $registration): void
    {
        $memberId = (int) ($registration->member_id ?? 0);
        if ($memberId <= 0) {
            return;
        }

        $type = match ((string) ($registration->program ?? '')) {
            SacramentProgramCycle::PROGRAM_FIRST_COMMUNION => MemberSacramentStatus::TYPE_COMMUNION,
            SacramentProgramCycle::PROGRAM_CONFIRMATION => MemberSacramentStatus::TYPE_CONFIRMATION,
            default => null,
        };

        if ($type === null) {
            return;
        }

        $status = (string) ($registration->status ?? '');
        $isReceived = in_array($status, [
            SacramentProgramRegistration::STATUS_COMPLETED,
            SacramentProgramRegistration::STATUS_ISSUED,
        ], true);

        $existing = MemberSacramentStatus::query()
            ->where('member_id', $memberId)
            ->where('sacrament_type', $type)
            ->first();

        MemberSacramentStatus::query()->updateOrCreate(
            [
                'member_id' => $memberId,
                'sacrament_type' => $type,
            ],
            [
                'uuid' => $existing?->uuid ?: (string) Str::uuid(),
                'is_received' => $isReceived,
                'certificate_no' => $existing?->certificate_no,
                'sacrament_date' => $isReceived
                    ? ($registration->issued_at?->toDateString() ?: $registration->completed_at?->toDateString())
                    : null,
                'source_type' => MemberSacramentStatus::SOURCE_PROGRAM_REGISTRATION,
                'source_record_id' => (int) $registration->id,
                'source_record_uuid' => (string) $registration->uuid,
            ]
        );
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
