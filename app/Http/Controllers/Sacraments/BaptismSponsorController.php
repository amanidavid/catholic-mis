<?php

namespace App\Http\Controllers\Sacraments;

use App\Http\Controllers\Controller;
use App\Models\People\Member;
use App\Models\Sacraments\Baptism;
use App\Models\Sacraments\BaptismSponsor;
use App\Traits\NormalizesNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BaptismSponsorController extends Controller
{
    public function store(Request $request, Baptism $baptism): RedirectResponse
    {
        $request->validate([
            'role' => ['nullable', 'string', 'max:100'],
            'member_uuid' => ['nullable', 'uuid'],
            'full_name' => ['nullable', 'string', 'max:190'],
            'parish_name' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
        ]);

        if ($baptism->status !== Baptism::STATUS_DRAFT) {
            return back()->with('error', 'Only draft requests can be updated.');
        }

        $memberUuid = $request->input('member_uuid');
        $fullName = $request->input('full_name');

        $hasMember = is_string($memberUuid) && trim($memberUuid) !== '';
        $hasName = is_string($fullName) && trim($fullName) !== '';

        if (! $hasMember && ! $hasName) {
            return back()->with('error', 'Select a sponsor member or enter sponsor full name.');
        }

        try {
            DB::transaction(function () use ($request, $baptism, $hasMember): void {
                $memberId = null;
                if ($hasMember) {
                    $memberId = (int) Member::query()->where('uuid', trim((string) $request->input('member_uuid')))->value('id');
                    if (! $memberId) {
                        throw new \RuntimeException('Invalid sponsor member.');
                    }
                }

                BaptismSponsor::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'baptism_id' => (int) $baptism->id,
                    'role' => $request->input('role') ?: null,
                    'member_id' => $memberId,
                    'full_name' => $hasMember ? null : NormalizesNames::normalize($request->input('full_name'), true),
                    'parish_name' => NormalizesNames::normalize($request->input('parish_name'), true),
                    'phone' => $request->input('phone') ?: null,
                    'email' => $request->input('email') ?: null,
                ]);
            });

            return back()->with('success', 'Sponsor added.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Baptism sponsor add failed', ['exception' => $e, 'baptism_uuid' => $baptism->uuid]);
            return back()->with('error', 'Unable to add sponsor. Please try again.');
        }
    }

    public function destroy(Request $request, Baptism $baptism, BaptismSponsor $sponsor): RedirectResponse
    {
        if ($baptism->status !== Baptism::STATUS_DRAFT) {
            return back()->with('error', 'Only draft requests can be updated.');
        }

        if ((int) $sponsor->baptism_id !== (int) $baptism->id) {
            return back()->with('error', 'Invalid sponsor record.');
        }

        try {
            $sponsor->delete();
            return back()->with('success', 'Sponsor removed.');
        } catch (\Throwable $e) {
            Log::error('Baptism sponsor remove failed', ['exception' => $e, 'baptism_uuid' => $baptism->uuid, 'sponsor_uuid' => $sponsor->uuid]);
            return back()->with('error', 'Unable to remove sponsor. Please try again.');
        }
    }
}
