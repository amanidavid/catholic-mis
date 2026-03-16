<?php

namespace App\Services\Certificates;

use App\Models\Certificates\CertificateIssuance;
use App\Models\Certificates\CertificateNumberRule;
use App\Models\Certificates\CertificateSequence;
use App\Models\Certificates\ParishSeal;
use App\Models\Sacraments\Baptism;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CertificateService
{
    public function issueBaptismCertificate(Baptism $baptism, User $issuer): CertificateIssuance
    {
        $parishId = (int) $baptism->parish_id;
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($baptism, $issuer, $parishId, $year): CertificateIssuance {
            $rule = CertificateNumberRule::query()->firstOrCreate(
                ['parish_id' => $parishId, 'entity_type' => 'baptism'],
                [
                    'prefix' => $baptism->parish?->code,
                    'sequence_padding' => 6,
                    'include_year' => true,
                ]
            );

            $sequence = CertificateSequence::query()
                ->where('parish_id', $parishId)
                ->where('entity_type', 'baptism')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = CertificateSequence::create([
                    'parish_id' => $parishId,
                    'entity_type' => 'baptism',
                    'year' => $year,
                    'next_number' => 1,
                ]);

                $sequence = CertificateSequence::query()
                    ->whereKey($sequence->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $next = (int) $sequence->next_number;
            $sequence->forceFill(['next_number' => $next + 1])->save();

            $prefix = is_string($rule->prefix ?? null) ? trim((string) $rule->prefix) : '';
            if ($prefix === '') {
                $prefix = is_string($baptism->parish?->code ?? null) ? trim((string) $baptism->parish?->code) : '';
            }
            if ($prefix === '') {
                $prefix = 'PAR';
            }

            $seqPadded = str_pad((string) $next, (int) $rule->sequence_padding, '0', STR_PAD_LEFT);
            $code = 'LB';

            $parts = [$prefix, $code];
            if ((bool) $rule->include_year) {
                $parts[] = (string) $year;
            }
            $parts[] = $seqPadded;

            $certificateNo = implode('/', $parts);
            $certificateNoKey = mb_strtolower($certificateNo, 'UTF-8');

            $member = $baptism->member()->first();
            $snapshot = [
                'type' => 'baptism',
                'certificate_no' => $certificateNo,
                'member' => $member ? [
                    'uuid' => $member->uuid,
                    'id' => $member->id,
                    'first_name' => $member->first_name,
                    'middle_name' => $member->middle_name,
                    'last_name' => $member->last_name,
                    'gender' => $member->gender,
                    'birth_date' => $member->birth_date?->toDateString(),
                    'jumuiya_id' => $member->jumuiya_id,
                ] : null,
                'baptism' => [
                    'uuid' => $baptism->uuid,
                    'birth_date' => $baptism->birth_date?->toDateString(),
                    'birth_town' => $baptism->birth_town,
                    'residence' => $baptism->residence,
                    'baptism_date' => $baptism->baptism_date?->toDateString(),
                    'baptism_parish_id' => $baptism->baptism_parish_id,
                    'father_member_id' => $baptism->father_member_id,
                    'father_name' => $baptism->father_name,
                    'mother_member_id' => $baptism->mother_member_id,
                    'mother_name' => $baptism->mother_name,
                    'sponsor_member_id' => $baptism->sponsor_member_id,
                    'sponsor_name' => $baptism->sponsor_name,
                    'minister_staff_id' => $baptism->minister_staff_id,
                    'minister_name' => $baptism->minister_name,
                ],
                'issued_by' => [
                    'id' => $issuer->id,
                    'uuid' => $issuer->uuid,
                    'email' => $issuer->email,
                    'name' => $issuer->name,
                ],
                'issued_at' => now()->toISOString(),
            ];

            $seal = ParishSeal::query()
                ->where('parish_id', $parishId)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();

            $issuance = CertificateIssuance::create([
                'uuid' => method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid(),
                'parish_id' => $parishId,
                'entity_type' => 'baptism',
                'entity_id' => $baptism->getKey(),
                'certificate_no' => $certificateNo,
                'certificate_no_key' => $certificateNoKey,
                'issued_at' => now(),
                'issued_by_user_id' => $issuer->id,
                'snapshot_json' => $snapshot,
                'seal_version' => $seal?->id,
            ]);

            $baptism->forceFill([
                'certificate_no' => $certificateNo,
                'certificate_no_key' => $certificateNoKey,
                'issued_at' => now(),
                'issued_by_user_id' => $issuer->id,
                'status' => 'issued',
            ])->save();

            return $issuance;
        }, 3);
    }
}
