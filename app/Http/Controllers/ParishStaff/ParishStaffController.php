<?php

namespace App\Http\Controllers\ParishStaff;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParishStaff\IndexParishStaffRequest;
use App\Http\Requests\ParishStaff\StoreParishStaffAssignmentRequest;
use App\Http\Requests\ParishStaff\UpdateParishStaffAssignmentRequest;
use App\Http\Requests\ParishStaff\RegisterParishStaffAsMemberRequest;
use App\Http\Requests\ParishStaff\StoreParishStaffRequest;
use App\Http\Requests\ParishStaff\UpdateParishStaffRequest;
use App\Http\Resources\Clergy\InstitutionResource;
use App\Models\Clergy\Institution;
use App\Models\ParishStaff;
use App\Models\ParishStaffAssignment;
use App\Models\ParishStaffAssignmentRole;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\People\MemberJumuiyaHistory;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Parish;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ParishStaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function transferMember(TransferParishStaffMemberRequest $request, ParishStaff $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $staff->parish_id !== $parishId) {
            return back()->with('error', 'Invalid staff record.');
        }

        if (! $staff->member_id) {
            return back()->with('error', 'This staff is not linked to a member.');
        }

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $request, $staff): void {
                $scopedJumuiyaId = null;
                if (! $request->user()?->can('jumuiyas.view')) {
                    $scopedJumuiyaId = $request->user()?->member?->jumuiya_id;
                }

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

                $member = Member::query()->lockForUpdate()->findOrFail($staff->member_id);
                $fromJumuiyaId = (int) $member->jumuiya_id;
                $previousFamilyId = (int) $member->family_id;

                $effectiveDate = ! empty($validated['effective_date'])
                    ? Carbon::parse($validated['effective_date'])->toDateString()
                    : now()->toDateString();

                if ($fromJumuiyaId !== $targetJumuiyaId) {
                    MemberJumuiyaHistory::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'member_id' => $member->id,
                        'from_jumuiya_id' => $fromJumuiyaId,
                        'to_jumuiya_id' => $targetJumuiyaId,
                        'effective_date' => $effectiveDate,
                        'reason' => $validated['reason'] ?? null,
                        'recorded_by_user_id' => (int) $request->user()->id,
                    ]);
                }

                $member->update([
                    'jumuiya_id' => $targetJumuiyaId,
                    'family_id' => $family->id,
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
            });

            return back()->with('success', 'Member transferred.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Transfer member failed', ['exception' => $e, 'staff_uuid' => $staff->uuid]);
            return back()->with('error', 'Unable to transfer member. Please try again.');
        }
    }

    public function registerAsMember(RegisterParishStaffAsMemberRequest $request, ParishStaff $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $staff->parish_id !== $parishId) {
            return back()->with('error', 'Invalid staff record.');
        }

        if ((int) ($staff->member_id ?? 0) > 0) {
            return back()->with('error', 'This staff is already linked to a member.');
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $staff, $request): void {
                $scopedJumuiyaId = null;
                if (! $request->user()?->can('jumuiyas.view')) {
                    $scopedJumuiyaId = $request->user()?->member?->jumuiya_id;
                }

                $jumuiyaId = (int) ($staff->jumuiya_id ?? 0);
                if (! $jumuiyaId && ! empty($data['jumuiya_uuid'])) {
                    $jumuiyaId = (int) Jumuiya::query()->where('uuid', $data['jumuiya_uuid'])->value('id');
                }

                if (! $jumuiyaId) {
                    throw new \InvalidArgumentException('Christian Community is required to register this staff as a member.');
                }

                if ($scopedJumuiyaId && $jumuiyaId !== (int) $scopedJumuiyaId) {
                    throw new \InvalidArgumentException('Invalid Christian Community.');
                }

                $firstName = trim((string) ($staff->first_name ?? ''));
                $lastName = trim((string) ($staff->last_name ?? ''));
                $gender = $staff->gender;

                if ($firstName === '' || $lastName === '') {
                    throw new \InvalidArgumentException('First name and last name are required to register this staff as a member.');
                }

                if (! is_string($gender) || ! in_array($gender, ['male', 'female'], true)) {
                    throw new \InvalidArgumentException('Gender is required to register this staff as a member.');
                }

                $baseFamilyName = trim(implode(' ', array_filter([$firstName, $lastName])));
                $familyName = $baseFamilyName;
                $suffix = 1;
                while (Family::query()->where('jumuiya_id', $jumuiyaId)->where('family_name', $familyName)->exists()) {
                    $suffix++;
                    $familyName = $baseFamilyName.' '.$suffix;
                }

                $family = Family::query()->create([
                    'jumuiya_id' => $jumuiyaId,
                    'family_name' => $familyName,
                    'family_code' => strtoupper(Str::random(8)),
                    'house_number' => null,
                    'street' => null,
                    'head_of_family_member_id' => null,
                    'is_active' => true,
                ]);

                $member = Member::query()->create([
                    'family_id' => $family->id,
                    'family_relationship_id' => null,
                    'jumuiya_id' => $jumuiyaId,
                    'first_name' => $firstName,
                    'middle_name' => $staff->middle_name ? trim((string) $staff->middle_name) : null,
                    'last_name' => $lastName,
                    'gender' => $gender,
                    'birth_date' => null,
                    'phone' => $staff->phone,
                    'email' => $staff->email,
                    'national_id' => $staff->national_id,
                    'marital_status' => null,
                    'is_active' => true,
                ]);

                $family->forceFill([
                    'head_of_family_member_id' => $member->id,
                ])->save();

                $staff->forceFill([
                    'member_id' => $member->id,
                    'jumuiya_id' => null,
                    'gender' => null,
                    'first_name' => null,
                    'middle_name' => null,
                    'last_name' => null,
                ])->save();

                $user = $staff->user()->first();
                if ($user) {
                    $user->forceFill([
                        'member_id' => $member->id,
                    ])->save();
                }
            });

            return back()->with('success', 'Staff registered as member.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Register staff as member failed', ['exception' => $e, 'staff_uuid' => $staff->uuid]);
            return back()->with('error', 'Unable to register staff as member. Please try again.');
        }
    }

    protected function currentParishId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user) return null;

        if (! empty($user->parish_id)) {
            return (int) $user->parish_id;
        }

        if ($user->can('permissions.manage')) {
            return Parish::query()->orderBy('id')->value('id');
        }

        return null;
    }

    public function index(IndexParishStaffRequest $request): Response
    {
        $this->authorize('viewAny', ParishStaff::class);

        $parishId = $this->currentParishId($request);
        if (! $parishId) {
            return Inertia::render('ParishStaff/Index', [
                'filters' => [
                    'q' => $request->query('q'),
                    'search_by' => $request->query('search_by') ?? 'name',
                    'is_active' => $request->query('is_active') ?? 'all',
                    'per_page' => (int) ($request->query('per_page') ?? 10),
                ],
                'staff' => [
                    'data' => [],
                    'links' => [],
                    'meta' => [
                        'current_page' => 1,
                        'from' => null,
                        'last_page' => 1,
                        'path' => $request->url(),
                        'per_page' => (int) ($request->query('per_page') ?? 10),
                        'to' => null,
                        'total' => 0,
                    ],
                ],
            ]);
        }

        $validated = $request->validated();
        $q = $validated['q'] ?? '';
        $searchBy = $validated['search_by'] ?? 'name';
        $status = $validated['is_active'] ?? 'all';
        $perPage = (int) ($validated['per_page'] ?? 10);

        $safe = addcslashes($q, '%_\\');

        $query = ParishStaff::query()
            ->select([
                'id',
                'uuid',
                'parish_id',
                'member_id',
                'jumuiya_id',
                'first_name',
                'middle_name',
                'last_name',
                'phone',
                'email',
                'national_id',
                'gender',
                'has_login',
                'is_active',
            ])
            ->where('parish_id', $parishId)
            ->with([
                'member:id,uuid,jumuiya_id,family_id,first_name,middle_name,last_name,gender,phone,email,national_id',
                'member.jumuiya:id,uuid,name,zone_id',
                'member.jumuiya.zone:id,uuid,name',
                'member.family:id,uuid,jumuiya_id,family_name',
                'jumuiya:id,uuid,name',
                'assignments' => fn ($qb) => $qb
                    ->select([
                        'id',
                        'uuid',
                        'parish_staff_id',
                        'institution_id',
                        'parish_staff_assignment_role_id',
                        'assignment_type',
                        'title',
                        'start_date',
                        'end_date',
                        'is_active',
                        'notes',
                    ])
                    ->with([
                        'role:id,uuid,name',
                        'institution:id,uuid,name,type,is_active',
                    ])
                    ->orderByDesc('start_date')
                    ->orderByDesc('id'),
            ])
            ->when($status === 'active', fn (Builder $qb) => $qb->where('is_active', true))
            ->when($status === 'inactive', fn (Builder $qb) => $qb->where('is_active', false))
            ->when($q !== '', function (Builder $qb) use ($safe, $searchBy) {
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

                if ($searchBy === 'assignment_type') {
                    $qb->whereHas('assignments', function (Builder $sub) use ($safe) {
                        $sub->where('assignment_type', 'like', $safe.'%');
                    });
                    return;
                }

                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('last_name', 'like', $safe.'%')
                        ->orWhere('first_name', 'like', $safe.'%')
                        ->orWhere('middle_name', 'like', $safe.'%');
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id');

        $paginated = $query->paginate($perPage)->withQueryString();

        $rows = $paginated->getCollection()->map(function (ParishStaff $s) {
            $member = $s->relationLoaded('member') ? $s->member : null;
            $jumuiya = $s->relationLoaded('jumuiya') ? $s->jumuiya : null;

            $family = null;
            if ($member && $member->relationLoaded('family')) {
                $family = $member->family;
            }

            $zone = null;
            if ($member && $member->relationLoaded('jumuiya') && $member->jumuiya && $member->jumuiya->relationLoaded('zone')) {
                $zone = $member->jumuiya->zone;
            }

            $memberJumuiya = null;
            if ($member && $member->relationLoaded('jumuiya')) {
                $memberJumuiya = $member->jumuiya;
            }

            $resolvedJumuiya = $memberJumuiya ?: $jumuiya;

            $name = trim(implode(' ', array_filter([
                $s->first_name,
                $s->middle_name,
                $s->last_name,
            ])));

            $editFirstName = $s->first_name;
            $editMiddleName = $s->middle_name;
            $editLastName = $s->last_name;

            $staffNameMissing = trim(implode(' ', array_filter([
                $editFirstName,
                $editMiddleName,
                $editLastName,
            ]))) === '';

            if ($name === '' && $member) {
                $name = trim(implode(' ', array_filter([
                    $member->first_name,
                    $member->middle_name,
                    $member->last_name,
                ])));
            }

            if ($staffNameMissing && $member) {
                $editFirstName = $member->first_name;
                $editMiddleName = $member->middle_name;
                $editLastName = $member->last_name;
            }

            $primaryAssignment = $s->relationLoaded('assignments')
                ? $s->assignments->firstWhere('is_active', true)
                : null;

            return [
                'uuid' => $s->uuid,
                'display_name' => $name !== '' ? $name : '-',
                'source' => $member ? 'Member' : 'External',
                'member_uuid' => $member?->uuid,
                'family_uuid' => $member ? $family?->uuid : null,
                'family_name' => $member ? $family?->family_name : null,
                'zone_uuid' => $member ? $zone?->uuid : null,
                'zone_name' => $member ? $zone?->name : null,
                'jumuiya_uuid' => $member ? null : $jumuiya?->uuid,
                'jumuiya_name' => $resolvedJumuiya?->name,
                'derived_jumuiya_uuid' => $memberJumuiya?->uuid,
                'derived_jumuiya_name' => $memberJumuiya?->name,
                'first_name' => $editFirstName,
                'middle_name' => $editMiddleName,
                'last_name' => $editLastName,
                'notes' => $s->notes,
                'phone' => $s->phone ?? $member?->phone,
                'email' => $s->email ?? $member?->email,
                'national_id' => $s->national_id ?? $member?->national_id,
                'gender' => $s->gender ?? $member?->gender,
                'is_active' => (bool) $s->is_active,
                'has_login' => (bool) $s->has_login,
                'current_assignment' => $primaryAssignment
                    ? [
                        'uuid' => $primaryAssignment->uuid,
                        'assignment_type' => $primaryAssignment->assignment_type,
                        'institution' => $primaryAssignment->relationLoaded('institution') && $primaryAssignment->institution
                            ? (new InstitutionResource($primaryAssignment->institution))->resolve()
                            : null,
                        'title' => $primaryAssignment->title,
                        'start_date' => $primaryAssignment->start_date?->format('Y-m-d'),
                        'end_date' => $primaryAssignment->end_date?->format('Y-m-d'),
                        'is_active' => (bool) $primaryAssignment->is_active,
                    ]
                    : null,
                'assignments' => $s->relationLoaded('assignments')
                    ? $s->assignments->map(fn ($a) => [
                        'uuid' => $a->uuid,
                        'role_uuid' => $a->role?->uuid,
                        'institution' => $a->relationLoaded('institution') && $a->institution
                            ? (new InstitutionResource($a->institution))->resolve()
                            : null,
                        'assignment_type' => $a->assignment_type,
                        'title' => $a->title,
                        'start_date' => $a->start_date?->format('Y-m-d'),
                        'end_date' => $a->end_date?->format('Y-m-d'),
                        'is_active' => (bool) $a->is_active,
                        'notes' => $a->notes,
                    ])->values()
                    : [],
            ];
        })->values();

        return Inertia::render('ParishStaff/Index', [
            'filters' => [
                'q' => $q,
                'search_by' => $searchBy,
                'is_active' => $status,
                'per_page' => $perPage,
            ],
            'assignmentRoles' => ParishStaffAssignmentRole::query()
                ->select(['uuid', 'name'])
                ->where('parish_id', $parishId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'staff' => [
                'data' => $rows,
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'from' => $paginated->firstItem(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'to' => $paginated->lastItem(),
                    'total' => $paginated->total(),
                    'links' => $paginated->linkCollection(),
                ],
            ],
        ]);
    }

    public function store(StoreParishStaffRequest $request): RedirectResponse
    {
        $this->authorize('create', ParishStaff::class);

        $parishId = $this->currentParishId($request);
        if (! $parishId) {
            return back()->with('error', 'Parish is not set for your account.');
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $parishId): void {
                $memberId = null;
                if (! empty($data['member_uuid'])) {
                    $memberId = (int) Member::query()->where('uuid', $data['member_uuid'])->value('id');
                }

                if ($memberId) {
                    $exists = ParishStaff::query()
                        ->where('parish_id', $parishId)
                        ->where('member_id', $memberId)
                        ->exists();

                    if ($exists) {
                        throw new \InvalidArgumentException('This member is already registered as staff.');
                    }
                }

                $jumuiyaId = null;
                if (! $memberId && ! empty($data['jumuiya_uuid'])) {
                    $jumuiyaId = (int) Jumuiya::query()->where('uuid', $data['jumuiya_uuid'])->value('id');
                }

                $gender = $memberId ? null : ($data['gender'] ?? null);

                $firstName = $data['first_name'] ?? null;
                $lastName = $data['last_name'] ?? null;

                if (! $memberId && (! is_string($firstName) || trim($firstName) === '' || ! is_string($lastName) || trim($lastName) === '')) {
                    throw new \InvalidArgumentException('First name and last name are required for external staff.');
                }

                ParishStaff::query()->create([
                    'parish_id' => $parishId,
                    'member_id' => $memberId,
                    'jumuiya_id' => $jumuiyaId,
                    'first_name' => $memberId ? null : ($data['first_name'] ?? null),
                    'middle_name' => $memberId ? null : ($data['middle_name'] ?? null),
                    'last_name' => $memberId ? null : ($data['last_name'] ?? null),
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'national_id' => $data['national_id'] ?? null,
                    'gender' => $gender,
                    'notes' => $data['notes'] ?? null,
                    'is_active' => true,
                    'has_login' => false,
                    'user_id' => null,
                ]);
            });

            return redirect()->route('parish-staff.index')->with('success', 'Staff saved.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Parish staff store failed', ['exception' => $e]);
            return back()->with('error', 'Unable to save staff. Please try again.');
        }
    }

    public function update(UpdateParishStaffRequest $request, ParishStaff $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $staff->parish_id !== $parishId) {
            return back()->with('error', 'Invalid staff record.');
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $staff): void {
                $memberId = null;
                if (! empty($data['member_uuid'])) {
                    $memberId = (int) Member::query()->where('uuid', $data['member_uuid'])->value('id');
                }

                $parishId = (int) $staff->parish_id;
                if ($memberId) {
                    $exists = ParishStaff::query()
                        ->where('parish_id', $parishId)
                        ->where('member_id', $memberId)
                        ->where('id', '!=', $staff->id)
                        ->exists();

                    if ($exists) {
                        throw new \InvalidArgumentException('This member is already registered as staff.');
                    }
                }

                $jumuiyaId = null;
                if (! $memberId && ! empty($data['jumuiya_uuid'])) {
                    $jumuiyaId = (int) Jumuiya::query()->where('uuid', $data['jumuiya_uuid'])->value('id');
                }

                $gender = $memberId ? null : ($data['gender'] ?? null);

                $firstName = $data['first_name'] ?? null;
                $lastName = $data['last_name'] ?? null;

                if (! $memberId && (! is_string($firstName) || trim($firstName) === '' || ! is_string($lastName) || trim($lastName) === '')) {
                    throw new \InvalidArgumentException('First name and last name are required for external staff.');
                }

                $staff->update([
                    'member_id' => $memberId,
                    'jumuiya_id' => $jumuiyaId,
                    'first_name' => $memberId ? null : ($data['first_name'] ?? null),
                    'middle_name' => $memberId ? null : ($data['middle_name'] ?? null),
                    'last_name' => $memberId ? null : ($data['last_name'] ?? null),
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'national_id' => $data['national_id'] ?? null,
                    'gender' => $gender,
                    'notes' => $data['notes'] ?? null,
                    'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : (bool) $staff->is_active,
                ]);
            });

            return back()->with('success', 'Staff updated.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Parish staff update failed', ['exception' => $e, 'staff_uuid' => $staff->uuid]);
            return back()->with('error', 'Unable to update staff. Please try again.');
        }
    }

    public function destroy(Request $request, ParishStaff $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $staff->parish_id !== $parishId) {
            return back()->with('error', 'Invalid staff record.');
        }

        if ($staff->assignments()->exists()) {
            return back()->with('error', 'Unable to delete. This staff has assignment history.');
        }

        try {
            $staff->delete();
            return back()->with('success', 'Staff deleted.');
        } catch (\Throwable $e) {
            Log::error('Parish staff delete failed', ['exception' => $e, 'staff_uuid' => $staff->uuid]);
            return back()->with('error', 'Unable to delete staff. Please try again.');
        }
    }

    public function storeAssignment(StoreParishStaffAssignmentRequest $request, ParishStaff $staff): RedirectResponse
    {
        $this->authorize('manageAssignments', $staff);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $staff->parish_id !== $parishId) {
            return back()->with('error', 'Invalid staff record.');
        }

        if ($staff->assignments()->exists()) {
            return back()->with('error', 'This staff already has an assignment. Edit the existing assignment instead.');
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $staff, $parishId): void {
                $roleId = null;
                $roleName = null;
                if (! empty($data['role_uuid'])) {
                    $role = ParishStaffAssignmentRole::query()
                        ->where('parish_id', $parishId)
                        ->where('uuid', $data['role_uuid'])
                        ->where('is_active', true)
                        ->first();

                    if (! $role) {
                        throw new \RuntimeException('Invalid assignment role.');
                    }

                    $roleId = $role->id;
                    $roleName = $role->name;
                }

                $institutionId = null;
                if (! empty($data['institution_uuid'])) {
                    $institution = Institution::query()
                        ->where('uuid', $data['institution_uuid'])
                        ->where('is_active', true)
                        ->first();

                    if (! $institution) {
                        throw new \RuntimeException('Invalid institution.');
                    }

                    $institutionId = (int) $institution->id;
                }

                ParishStaffAssignment::query()
                    ->where('parish_staff_id', $staff->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                ParishStaffAssignment::query()->create([
                    'parish_staff_id' => $staff->id,
                    'institution_id' => $institutionId,
                    'parish_staff_assignment_role_id' => $roleId,
                    'assignment_type' => $roleName,
                    'title' => $data['title'] ?? null,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'] ?? null,
                    'is_active' => true,
                    'notes' => $data['notes'] ?? null,
                ]);
            });

            return back()->with('success', 'Assignment saved.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Staff assignment store failed', ['exception' => $e, 'staff_uuid' => $staff->uuid]);
            return back()->with('error', 'Unable to save assignment. Please try again.');
        }
    }

    public function updateAssignment(UpdateParishStaffAssignmentRequest $request, ParishStaff $staff, ParishStaffAssignment $assignment): RedirectResponse
    {
        $this->authorize('manageAssignments', $staff);

        if ((int) $assignment->parish_staff_id !== (int) $staff->id) {
            return back()->with('error', 'Invalid assignment.');
        }

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $staff->parish_id !== $parishId) {
            return back()->with('error', 'Invalid staff record.');
        }

        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $assignment, $staff, $parishId): void {
                $roleId = null;
                $roleName = null;
                if (! empty($data['role_uuid'])) {
                    $role = ParishStaffAssignmentRole::query()
                        ->where('parish_id', $parishId)
                        ->where('uuid', $data['role_uuid'])
                        ->where('is_active', true)
                        ->first();

                    if (! $role) {
                        throw new \RuntimeException('Invalid assignment role.');
                    }

                    $roleId = $role->id;
                    $roleName = $role->name;
                }

                $institutionId = null;
                if (! empty($data['institution_uuid'])) {
                    $institution = Institution::query()
                        ->where('uuid', $data['institution_uuid'])
                        ->where('is_active', true)
                        ->first();

                    if (! $institution) {
                        throw new \RuntimeException('Invalid institution.');
                    }

                    $institutionId = (int) $institution->id;
                }

                $willBeActive = (bool) ($data['is_active'] ?? false);
                if ($willBeActive) {
                    ParishStaffAssignment::query()
                        ->where('parish_staff_id', $staff->id)
                        ->where('id', '!=', $assignment->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);
                }

                $assignment->update([
                    'institution_id' => $institutionId,
                    'parish_staff_assignment_role_id' => $roleId,
                    'assignment_type' => $roleName,
                    'title' => $data['title'] ?? null,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'] ?? null,
                    'is_active' => $willBeActive,
                    'notes' => $data['notes'] ?? null,
                ]);
            });

            return back()->with('success', 'Assignment updated.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Staff assignment update failed', ['exception' => $e, 'staff_uuid' => $staff->uuid, 'assignment_uuid' => $assignment->uuid]);
            return back()->with('error', 'Unable to update assignment. Please try again.');
        }
    }

    public function createLogin(Request $request, ParishStaff $staff): RedirectResponse
    {
        $this->authorize('manageLogin', $staff);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $staff->parish_id !== $parishId) {
            return back()->with('error', 'Invalid staff record.');
        }

        $member = $staff->member()->first();
        $email = trim((string) ($staff->email ?? $member?->email ?? ''));
        if ($email === '') {
            return back()->with('error', 'Email is required to create a login account.');
        }

        try {
            $tempPassword = null;

            $user = DB::transaction(function () use ($staff, $email, $parishId, $member, &$tempPassword): User {
                $existingByStaff = $staff->user()->first();
                if ($existingByStaff) {
                    $tempPassword = Str::password(12);

                    $existingByStaff->forceFill([
                        'email' => $email,
                        'parish_id' => $parishId,
                        'is_active' => true,
                        'must_change_password' => true,
                        'password' => Hash::make($tempPassword),
                    ])->save();

                    $staff->forceFill([
                        'has_login' => true,
                        'user_id' => $existingByStaff->id,
                    ])->save();

                    return $existingByStaff;
                }

                $existingByEmail = User::query()->where('email', $email)->first();
                if ($existingByEmail) {
                    if ($existingByEmail->member_id && $member && (int) $existingByEmail->member_id !== (int) $member->id) {
                        throw new \RuntimeException('This email is already used by another account.');
                    }

                    if ($existingByEmail->member_id && ! $member) {
                        throw new \RuntimeException('This email is already used by another account.');
                    }

                    $tempPassword = Str::password(12);

                    $existingByEmail->forceFill([
                        'member_id' => $member?->id,
                        'parish_id' => $parishId,
                        'is_active' => true,
                        'must_change_password' => true,
                        'password' => Hash::make($tempPassword),
                    ])->save();

                    $staff->forceFill([
                        'has_login' => true,
                        'user_id' => $existingByEmail->id,
                    ])->save();

                    return $existingByEmail;
                }

                $fullName = trim(implode(' ', array_filter([
                    $staff->first_name ?? $member?->first_name,
                    $staff->middle_name ?? $member?->middle_name,
                    $staff->last_name ?? $member?->last_name,
                ])));

                $tempPassword = Str::password(12);

                $user = User::create([
                    'name' => $fullName !== '' ? $fullName : $email,
                    'email' => $email,
                    'password' => Hash::make($tempPassword),
                    'member_id' => $member?->id,
                    'parish_id' => $parishId,
                    'user_category' => 'staff',
                    'is_active' => true,
                    'must_change_password' => true,
                ]);

                $staff->forceFill([
                    'has_login' => true,
                    'user_id' => $user->id,
                ])->save();

                return $user;
            });

            if (is_string($tempPassword) && $tempPassword !== '') {
                return back()->with('success', 'Login enabled. Temporary password: '.$tempPassword.' (user will be forced to change password at first login)');
            }

            return back()->with('success', 'Login enabled. User will be forced to change password at first login.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Staff login provision failed', ['exception' => $e, 'staff_uuid' => $staff->uuid]);
            return back()->with('error', 'Unable to create login. Please try again.');
        }
    }

    public function disableLogin(Request $request, ParishStaff $staff): RedirectResponse
    {
        $this->authorize('manageLogin', $staff);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $staff->parish_id !== $parishId) {
            return back()->with('error', 'Invalid staff record.');
        }

        try {
            DB::transaction(function () use ($staff): void {
                $user = $staff->user()->first();
                if ($user) {
                    $user->forceFill([
                        'is_active' => false,
                    ])->save();
                }

                $staff->forceFill([
                    'has_login' => false,
                ])->save();
            });

            return back()->with('success', 'Login disabled.');
        } catch (\Throwable $e) {
            Log::error('Staff login disable failed', ['exception' => $e, 'staff_uuid' => $staff->uuid]);
            return back()->with('error', 'Unable to disable login. Please try again.');
        }
    }
}
