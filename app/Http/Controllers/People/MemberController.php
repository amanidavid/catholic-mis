<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\IndexMembersRequest;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\TransferMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Http\Resources\People\MemberResource;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\People\Family;
use App\Models\People\FamilyRelationship;
use App\Models\People\Member;
use App\Models\People\MemberJumuiyaHistory;
use App\Models\Structure\Jumuiya;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function scopedJumuiyaId(Request $request): ?int
    {
        if ($request->user()?->can('jumuiyas.view')) {
            return null;
        }

        $userMemberId = (int) ($request->user()?->member_id ?? $request->user()?->member?->id ?? 0);
        if ($userMemberId) {
            $today = now()->toDateString();

            $leaderJumuiyaId = JumuiyaLeadership::query()
                ->where('member_id', $userMemberId)
                ->where('is_active', true)
                ->whereDate('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                })
                ->orderByDesc('start_date')
                ->value('jumuiya_id');

            if ($leaderJumuiyaId) {
                return (int) $leaderJumuiyaId;
            }
        }

        return $request->user()?->member?->jumuiya_id;
    }

    public function index(IndexMembersRequest $request): Response
    {
        $this->authorize('viewAny', Member::class);

        $validated = $request->validated();
        $q = $validated['q'] ?? null;
        $searchBy = $validated['search_by'] ?? 'name';
        $perPage = (int) ($validated['per_page'] ?? 10);
        $jumuiyaUuid = $validated['jumuiya_uuid'] ?? null;
        $familyUuid = $validated['family_uuid'] ?? null;

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        $selectedJumuiya = null;
        if (is_string($jumuiyaUuid) && $jumuiyaUuid !== '') {
            $selectedJumuiya = Jumuiya::query()->where('uuid', $jumuiyaUuid)->first();
        }

        if ($scopedJumuiyaId && $selectedJumuiya && $selectedJumuiya->id !== $scopedJumuiyaId) {
            $selectedJumuiya = null;
        }

        $selectedFamily = null;
        if (is_string($familyUuid) && $familyUuid !== '') {
            $selectedFamily = Family::query()->where('uuid', $familyUuid)->first();
        }

        if ($selectedFamily) {
            if ($scopedJumuiyaId && $selectedFamily->jumuiya_id !== $scopedJumuiyaId) {
                $selectedFamily = null;
            }

            if ($selectedJumuiya && $selectedFamily->jumuiya_id !== $selectedJumuiya->id) {
                $selectedFamily = null;
            }
        }

        $membersQuery = Member::query()
            ->with([
                'jumuiya:id,uuid,name',
                'family:id,uuid,family_name',
                'user:id,member_id',
                'user.roles:id,name',
            ])
            ->when($scopedJumuiyaId, fn (Builder $qb) => $qb->where('jumuiya_id', $scopedJumuiyaId))
            ->when($selectedJumuiya, fn (Builder $qb) => $qb->where('jumuiya_id', $selectedJumuiya->id))
            ->when($selectedFamily, fn (Builder $qb) => $qb->where('family_id', $selectedFamily->id))
            ->when(is_string($q) && $q !== '', function (Builder $qb) use ($q, $searchBy) {
                $safe = addcslashes($q, '%_\\');

                if ($searchBy === 'phone') {
                    $qb->where('phone', 'like', $safe.'%');
                    return;
                }

                if ($searchBy === 'email') {
                    $qb->where('email', 'like', $safe.'%');
                    return;
                }

                if ($searchBy === 'national_id') {
                    $qb->where('national_id', 'like', $safe.'%');
                    return;
                }

                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('last_name', 'like', $safe.'%')
                        ->orWhere('first_name', 'like', $safe.'%')
                        ->orWhere('middle_name', 'like', $safe.'%');
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        $members = $membersQuery->paginate($perPage)->withQueryString();

        $jumuiyas = Jumuiya::query()
            ->when($scopedJumuiyaId, fn (Builder $qb) => $qb->where('id', $scopedJumuiyaId))
            ->orderBy('name')
            ->get(['uuid', 'name']);

        return Inertia::render('Members/Index', [
            'filters' => [
                'q' => $q,
                'search_by' => $searchBy,
                'jumuiya_uuid' => $jumuiyaUuid,
                'family_uuid' => $familyUuid,
                'per_page' => $perPage,
            ],
            'jumuiyas' => $jumuiyas,
            'members' => MemberResource::collection($members),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Member::class);

        $defaultJumuiyaUuid = $request->query('jumuiya_uuid');
        $jumuiyaUuidFromQuery = is_string($defaultJumuiyaUuid) && $defaultJumuiyaUuid !== '';
        $defaultZoneUuid = null;
        $defaultZoneName = null;
        $defaultJumuiyaName = null;

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId && (! is_string($defaultJumuiyaUuid) || $defaultJumuiyaUuid === '')) {
            $scoped = Jumuiya::query()
                ->where('id', $scopedJumuiyaId)
                ->with('zone:id,uuid,name')
                ->first();

            if ($scoped) {
                $defaultJumuiyaUuid = $scoped->uuid;
                $defaultJumuiyaName = $scoped->name;
                $defaultZoneUuid = $scoped->zone?->uuid;
                $defaultZoneName = $scoped->zone?->name;
            }
        }

        if (is_string($defaultJumuiyaUuid) && $defaultJumuiyaUuid !== '' && ! $defaultZoneUuid) {
            $jumuiya = Jumuiya::query()
                ->where('uuid', $defaultJumuiyaUuid)
                ->with('zone:id,uuid,name')
                ->first();

            $defaultZoneUuid = $jumuiya?->zone?->uuid;
            $defaultZoneName = $jumuiya?->zone?->name;
            $defaultJumuiyaName = $jumuiya?->name;
        }

        return Inertia::render('Members/Create', [
            'defaults' => [
                'zone_uuid' => $defaultZoneUuid,
                'zone_name' => $defaultZoneName,
                'jumuiya_uuid' => $defaultJumuiyaUuid,
                'jumuiya_uuid_from_query' => $jumuiyaUuidFromQuery,
                'jumuiya_name' => $defaultJumuiyaName,
                'family_uuid' => $request->query('family_uuid'),
            ],
        ]);
    }

    public function edit(Request $request, Member $member): Response
    {
        $this->authorize('update', $member);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId && $member->jumuiya_id !== $scopedJumuiyaId) {
            abort(404);
        }

        $member->load([
            'jumuiya:id,uuid,name,zone_id',
            'jumuiya.zone:id,uuid,name',
            'family:id,uuid,family_name,head_of_family_member_id',
            'familyRelationship:id,uuid,name',
        ]);

        return Inertia::render('Members/Edit', [
            'member' => new MemberResource($member),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Member::class);

        $q = $request->query('q');
        $jumuiyaUuid = $request->query('jumuiya_uuid');
        $familyUuid = $request->query('family_uuid');
        $excludeUuids = $request->query('exclude_uuids');

        $q = is_string($q) ? trim($q) : '';

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        $selectedJumuiyaId = null;
        if (is_string($jumuiyaUuid) && $jumuiyaUuid !== '') {
            $selectedJumuiyaId = Jumuiya::query()->where('uuid', $jumuiyaUuid)->value('id');
        }

        $selectedFamilyId = null;
        if (is_string($familyUuid) && $familyUuid !== '') {
            $selectedFamilyId = Family::query()->where('uuid', $familyUuid)->value('id');
        }

        $excluded = [];
        if (is_string($excludeUuids) && trim($excludeUuids) !== '') {
            $excluded = array_values(array_filter(array_map('trim', explode(',', $excludeUuids))));
        }

        if ($scopedJumuiyaId && $selectedJumuiyaId && $selectedJumuiyaId !== $scopedJumuiyaId) {
            $selectedJumuiyaId = null;
        }

        $safe = addcslashes($q, '%_\\');

        $members = Member::query()
            ->select(['id', 'uuid', 'jumuiya_id', 'first_name', 'middle_name', 'last_name', 'gender', 'email', 'phone'])
            ->with(['jumuiya:id,uuid,name'])
            ->when($scopedJumuiyaId, fn (Builder $qb) => $qb->where('jumuiya_id', $scopedJumuiyaId))
            ->when($selectedJumuiyaId, fn (Builder $qb) => $qb->where('jumuiya_id', $selectedJumuiyaId))
            ->when($selectedFamilyId, fn (Builder $qb) => $qb->where('family_id', $selectedFamilyId))
            ->when(! empty($excluded), fn (Builder $qb) => $qb->whereNotIn('uuid', $excluded))
            ->when($q !== '', function (Builder $qb) use ($safe) {
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('first_name', 'like', $safe.'%')
                        ->orWhere('middle_name', 'like', $safe.'%')
                        ->orWhere('last_name', 'like', $safe.'%')
                        ->orWhere('email', 'like', $safe.'%')
                        ->orWhere('phone', 'like', $safe.'%');
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get()
            ->map(function (Member $m) {
                $fullName = trim(implode(' ', array_filter([$m->first_name, $m->middle_name, $m->last_name])));

                return [
                    'uuid' => $m->uuid,
                    'name' => $fullName,
                    'jumuiya_uuid' => $m->jumuiya?->uuid,
                    'jumuiya_name' => $m->jumuiya?->name,
                    'gender' => $m->gender,
                    'email' => $m->email,
                    'phone' => $m->phone,
                ];
            })
            ->values();

        return response()->json(['data' => $members]);
    }

    public function transfer(TransferMemberRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $validated = $request->validated();

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        try {
            DB::transaction(function () use ($validated, $request, $member, $scopedJumuiyaId): void {
                $targetJumuiyaId = (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id');
                if (! $targetJumuiyaId) {
                    throw new \InvalidArgumentException('Invalid Christian Community.');
                }

                if ($scopedJumuiyaId && $targetJumuiyaId !== (int) $scopedJumuiyaId) {
                    throw new \InvalidArgumentException('Invalid Christian Community.');
                }

                $family = Family::query()->where('uuid', $validated['family_uuid'])->firstOrFail();
                if ((int) $family->jumuiya_id !== $targetJumuiyaId) {
                    throw new \InvalidArgumentException('Invalid family for this Christian Community.');
                }

                $lockedMember = Member::query()->lockForUpdate()->findOrFail($member->id);
                $fromJumuiyaId = (int) $lockedMember->jumuiya_id;
                $previousFamilyId = (int) $lockedMember->family_id;

                $effectiveDate = ! empty($validated['effective_date'])
                    ? Carbon::parse($validated['effective_date'])->toDateString()
                    : now()->toDateString();

                if ($fromJumuiyaId !== $targetJumuiyaId) {
                    MemberJumuiyaHistory::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'member_id' => $lockedMember->id,
                        'from_jumuiya_id' => $fromJumuiyaId,
                        'to_jumuiya_id' => $targetJumuiyaId,
                        'effective_date' => $effectiveDate,
                        'reason' => $validated['reason'] ?? null,
                        'recorded_by_user_id' => (int) $request->user()->id,
                    ]);
                }

                $lockedMember->update([
                    'jumuiya_id' => $targetJumuiyaId,
                    'family_id' => $family->id,
                ]);

                if ($previousFamilyId && $previousFamilyId !== (int) $family->id) {
                    $previousHeadId = (int) Family::query()
                        ->where('id', $previousFamilyId)
                        ->value('head_of_family_member_id');

                    if ($previousHeadId === (int) $lockedMember->id) {
                        Family::query()->where('id', $previousFamilyId)->update([
                            'head_of_family_member_id' => null,
                        ]);
                    }
                }
            });

            return back()->with('success', 'Member transferred.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Member transfer failed', ['exception' => $e, 'member_uuid' => $member->uuid]);
            return back()->with('error', 'Unable to transfer member. Please try again.');
        }
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $this->authorize('create', Member::class);

        $validated = $request->validated();

        $jumuiya = Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->firstOrFail();
        $family = Family::query()->where('uuid', $validated['family_uuid'])->firstOrFail();

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId && $jumuiya->id !== $scopedJumuiyaId) {
            return back()->with('error', 'Invalid Christian Community.');
        }

        if ($family->jumuiya_id !== $jumuiya->id) {
            return back()->with('error', 'Invalid family for this Christian Community.');
        }

        $familyRelationshipId = null;
        if (! empty($validated['family_relationship_uuid'])) {
            $familyRelationshipId = (int) FamilyRelationship::query()
                ->where('uuid', $validated['family_relationship_uuid'])
                ->where('is_active', true)
                ->value('id');

            if (! $familyRelationshipId) {
                return back()->with('error', 'Invalid family relationship.');
            }
        }

        try {
            DB::transaction(function () use ($validated, $jumuiya, $family, $familyRelationshipId): void {
                $member = Member::query()->create([
                    'jumuiya_id' => $jumuiya->id,
                    'family_id' => $family->id,
                    'family_relationship_id' => $familyRelationshipId,
                    'first_name' => trim($validated['first_name']),
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name' => trim($validated['last_name']),
                    'gender' => $validated['gender'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'national_id' => $validated['national_id'] ?? null,
                    'marital_status' => $validated['marital_status'] ?? null,
                    'is_active' => true,
                ]);

                $isHead = array_key_exists('is_head_of_family', $validated) ? (bool) $validated['is_head_of_family'] : false;
                if ($isHead) {
                    Family::query()->where('id', $family->id)->update([
                        'head_of_family_member_id' => $member->id,
                    ]);
                }
            });

            return redirect()->route('members.index')->with('success', 'Member saved.');
        } catch (\Throwable $e) {
            Log::error('Member store failed', ['exception' => $e]);

            return back()->with('error', 'Unable to save member. Please try again.');
        }
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId && $member->jumuiya_id !== $scopedJumuiyaId) {
            return back()->with('error', 'Invalid member.');
        }

        $validated = $request->validated();

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        $targetJumuiyaId = $member->jumuiya_id;
        if (! empty($validated['jumuiya_uuid'])) {
            $targetJumuiyaId = (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id');
            if (! $targetJumuiyaId) {
                return back()->with('error', 'Invalid Christian Community.');
            }
        }

        if ($scopedJumuiyaId && $targetJumuiyaId !== $scopedJumuiyaId) {
            return back()->with('error', 'Invalid Christian Community.');
        }

        $family = Family::query()->where('uuid', $validated['family_uuid'])->firstOrFail();
        if ($family->jumuiya_id !== $targetJumuiyaId) {
            return back()->with('error', 'Invalid family for this Christian Community.');
        }

        $familyRelationshipId = null;
        if (! empty($validated['family_relationship_uuid'])) {
            $familyRelationshipId = (int) FamilyRelationship::query()
                ->where('uuid', $validated['family_relationship_uuid'])
                ->where('is_active', true)
                ->value('id');

            if (! $familyRelationshipId) {
                return back()->with('error', 'Invalid family relationship.');
            }
        }

        try {
            $previousFamilyId = (int) $member->family_id;

            DB::transaction(function () use ($validated, $member, $targetJumuiyaId, $family, $previousFamilyId, $familyRelationshipId): void {
                $member->update([
                    'jumuiya_id' => $targetJumuiyaId,
                    'family_id' => $family->id,
                    'family_relationship_id' => $familyRelationshipId,
                    'first_name' => trim($validated['first_name']),
                    'middle_name' => $validated['middle_name'] ?? null,
                    'last_name' => trim($validated['last_name']),
                    'gender' => $validated['gender'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'national_id' => $validated['national_id'] ?? null,
                    'marital_status' => $validated['marital_status'] ?? null,
                    'is_active' => (bool) $validated['is_active'],
                ]);

                if ($previousFamilyId && $previousFamilyId !== (int) $family->id) {
                    $previousHeadId = (int) Family::query()
                        ->where('id', $previousFamilyId)
                        ->value('head_of_family_member_id');

                    if ($previousHeadId === (int) $member->id) {
                        Family::query()->where('id', $previousFamilyId)->update([
                            'head_of_family_member_id' => null,
                        ]);
                    }
                }

                $isHead = array_key_exists('is_head_of_family', $validated) ? (bool) $validated['is_head_of_family'] : false;
                if ($isHead) {
                    Family::query()->where('id', $family->id)->update([
                        'head_of_family_member_id' => $member->id,
                    ]);
                    return;
                }

                $currentHeadId = (int) Family::query()->where('id', $family->id)->value('head_of_family_member_id');
                if ($currentHeadId === (int) $member->id) {
                    Family::query()->where('id', $family->id)->update([
                        'head_of_family_member_id' => null,
                    ]);
                }
            });

            return redirect()->route('members.index')->with('success', 'Member updated.');
        } catch (\Throwable $e) {
            Log::error('Member update failed', ['exception' => $e, 'member_uuid' => $member->uuid]);

            return back()->with('error', 'Unable to update member. Please try again.');
        }
    }

    public function destroy(Request $request, Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId && $member->jumuiya_id !== $scopedJumuiyaId) {
            return back()->with('error', 'Invalid member.');
        }

        if (JumuiyaLeadership::query()->where('member_id', $member->id)->exists()) {
            return back()->with('error', 'Unable to delete. This member has leadership history.');
        }

        try {
            $member->delete();

            return back()->with('success', 'Member deleted.');
        } catch (\Throwable $e) {
            Log::error('Member delete failed', ['exception' => $e, 'member_uuid' => $member->uuid]);

            return back()->with('error', 'Unable to delete member. Please try again.');
        }
    }
}
