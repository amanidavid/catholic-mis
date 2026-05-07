<?php

namespace App\Http\Controllers\Kitume;

use App\Http\Controllers\Concerns\ProvisionsMemberLoginAccess;
use App\Http\Controllers\Concerns\ResolvesOutstationScope;
use App\Http\Controllers\Concerns\ResolvesSingleParishContext;
use App\Http\Controllers\Controller;
use App\Http\Resources\Kitume\ParishAssociationLeadershipResource;
use App\Http\Resources\Kitume\ParishAssociationMemberResource;
use App\Http\Resources\Kitume\ParishAssociationResource;
use App\Models\Kitume\ParishAssociation;
use App\Models\Kitume\ParishAssociationLeaderRole;
use App\Models\Kitume\ParishAssociationLeadership;
use App\Models\Kitume\ParishAssociationMember;
use App\Models\People\Member;
use App\Models\Structure\Outstation;
use App\Traits\NormalizesNames;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ParishAssociationController extends Controller
{
    use ProvisionsMemberLoginAccess;
    use ResolvesOutstationScope;
    use ResolvesSingleParishContext;

    private const VIEW_PERMISSION = 'parish-associations.view';
    private const VIEW_ALL_PERMISSION = 'parish-associations.view-all';
    private const MEMBER_VIEW_PERMISSION = 'parish-associations.members.view';
    private const LEADERSHIP_VIEW_PERMISSION = 'parish-associations.leadership.view';
    private const LEADER_ROLE_VIEW_PERMISSION = 'parish-associations.leader-roles.view';
    private const CREATE_PERMISSION = 'parish-associations.create';
    private const UPDATE_PERMISSION = 'parish-associations.update';
    private const DELETE_PERMISSION = 'parish-associations.delete';
    private const MEMBER_CREATE_PERMISSION = 'parish-associations.members.create';
    private const MEMBER_UPDATE_PERMISSION = 'parish-associations.members.update';
    private const MEMBER_DELETE_PERMISSION = 'parish-associations.members.delete';
    private const LEADERSHIP_CREATE_PERMISSION = 'parish-associations.leadership.create';
    private const LEADERSHIP_UPDATE_PERMISSION = 'parish-associations.leadership.update';
    private const LEADERSHIP_DELETE_PERMISSION = 'parish-associations.leadership.delete';
    private const LEADER_ROLE_CREATE_PERMISSION = 'parish-associations.leader-roles.create';
    private const LEADER_ROLE_UPDATE_PERMISSION = 'parish-associations.leader-roles.update';
    private const LEADER_ROLE_DELETE_PERMISSION = 'parish-associations.leader-roles.delete';

    private const LEADER_LOGIN_PERMISSIONS = [
        self::VIEW_PERMISSION,
        self::MEMBER_VIEW_PERMISSION,
        self::LEADERSHIP_VIEW_PERMISSION,
        self::LEADER_ROLE_VIEW_PERMISSION,
        'reports.associations.view',
    ];

    private static ?bool $memberSearchKeyColumnsAvailable = null;

    private function activeTodayConstraint(
        QueryBuilder|\Illuminate\Database\Eloquent\Builder|Relation $query,
        string $endDateColumn = 'end_date'
    ): void
    {
        $today = now()->toDateString();
        $query->where('is_active', true)
            ->where(function ($inner) use ($endDateColumn, $today) {
                $inner->whereNull($endDateColumn)->orWhereDate($endDateColumn, '>=', $today);
            });
    }

    private function parishMemberQuery(int $parishId): \Illuminate\Database\Eloquent\Builder
    {
        return Member::query()->whereExists(function ($sub) use ($parishId) {
            $sub->select(DB::raw(1))
                ->from('jumuiyas')
                ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                ->whereColumn('jumuiyas.id', 'members.jumuiya_id')
                ->where('zones.parish_id', $parishId);
        });
    }

    private function fullKitumeAccess(Request $request): bool
    {
        $user = $request->user();

        return (bool) (
            $user?->can(self::VIEW_ALL_PERMISSION)
            || $user?->can('permissions.manage')
        );
    }

    private function scopedAssociationIds(Request $request): ?array
    {
        if ($this->fullKitumeAccess($request)) {
            return null;
        }

        $memberId = (int) ($request->user()?->member_id ?? 0);
        if ($memberId <= 0) {
            return [];
        }

        return ParishAssociationLeadership::query()
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->distinct()
            ->pluck('parish_association_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function associationOrFail(string $uuid, int $parishId): ParishAssociation
    {
        return ParishAssociation::query()
            ->where('parish_id', $parishId)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function visibleAssociationOrFail(Request $request, string $uuid, int $parishId): ParishAssociation
    {
        $association = $this->associationOrFail($uuid, $parishId);
        $this->authorize('view', $association);

        return $association;
    }

    private function canReadLeaderRoles(Request $request): bool
    {
        $user = $request->user();

        return (bool) (
            $user?->can(self::LEADER_ROLE_VIEW_PERMISSION)
            || $user?->can(self::LEADER_ROLE_CREATE_PERMISSION)
            || $user?->can(self::LEADER_ROLE_UPDATE_PERMISSION)
            || $user?->can(self::LEADER_ROLE_DELETE_PERMISSION)
            || $user?->can(self::LEADERSHIP_CREATE_PERMISSION)
            || $user?->can(self::LEADERSHIP_UPDATE_PERMISSION)
            || $user?->can(self::LEADERSHIP_DELETE_PERMISSION)
        );
    }

    private function escapedPrefixLike(?string $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            return null;
        }

        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
    }

    private function normalizedPrefixLike(?string $value): ?string
    {
        $normalized = NormalizesNames::normalize(is_string($value) ? $value : null);
        $normalized = $normalized !== null ? mb_strtolower($normalized, 'UTF-8') : '';

        return $this->escapedPrefixLike($normalized);
    }

    private function memberSearchKeyColumnsAvailable(): bool
    {
        if (self::$memberSearchKeyColumnsAvailable !== null) {
            return self::$memberSearchKeyColumnsAvailable;
        }

        return self::$memberSearchKeyColumnsAvailable =
            Schema::hasColumn('members', 'first_name_key')
            && Schema::hasColumn('members', 'last_name_key')
            && Schema::hasColumn('members', 'full_name_key');
    }

    private function applyMemberSearchConstraint(
        QueryBuilder|\Illuminate\Database\Eloquent\Builder|Relation $query,
        ?string $rawQuery
    ): void
    {
        $rawQuery = is_string($rawQuery) ? trim($rawQuery) : '';
        if ($rawQuery === '') {
            return;
        }

        $prefix = $this->escapedPrefixLike($rawQuery);
        $normalizedPrefix = $this->normalizedPrefixLike($rawQuery);
        $numericPrefix = $this->escapedPrefixLike(preg_replace('/\s+/', '', $rawQuery) ?? $rawQuery);
        $looksLikeEmail = str_contains($rawQuery, '@');
        $looksLikePhone = preg_match('/^[0-9+\-()\s]+$/', $rawQuery) === 1;
        $hasNameKeyColumns = $this->memberSearchKeyColumnsAvailable();

        $query->where(function ($inner) use (
            $looksLikeEmail,
            $looksLikePhone,
            $prefix,
            $numericPrefix,
            $normalizedPrefix,
            $hasNameKeyColumns
        ) {
            if ($looksLikeEmail && $prefix) {
                $inner->where('email', 'like', $prefix);
                return;
            }

            if ($looksLikePhone && $numericPrefix) {
                $inner->where('phone', 'like', $numericPrefix);
                return;
            }

            if ($hasNameKeyColumns && $normalizedPrefix) {
                $inner->where('full_name_key', 'like', $normalizedPrefix)
                    ->orWhere('first_name_key', 'like', $normalizedPrefix)
                    ->orWhere('last_name_key', 'like', $normalizedPrefix);
            } elseif ($normalizedPrefix) {
                $inner->where('first_name', 'like', $normalizedPrefix)
                    ->orWhere('last_name', 'like', $normalizedPrefix);
            }

            if ($prefix) {
                $inner->orWhere('phone', 'like', $prefix)
                    ->orWhere('email', 'like', $prefix);
            }
        });
    }

    private function associationsQuery(
        Request $request,
        int $parishId,
        bool $activeOnly,
        ?string $groupsPrefix,
        ?string $groupsNormalizedPrefix,
        string $groupsStatus,
        string $groupsSort
    ): \Illuminate\Database\Eloquent\Builder
    {
        $scopedAssociationIds = $this->scopedAssociationIds($request);

        return ParishAssociation::query()
            ->where('parish_id', $parishId)
            ->when(is_array($scopedAssociationIds), fn ($q) => $q->whereIn('id', $scopedAssociationIds ?: [0]))
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->when($groupsPrefix || $groupsNormalizedPrefix, function ($q) use ($groupsPrefix, $groupsNormalizedPrefix) {
                $q->where(function ($inner) use ($groupsPrefix, $groupsNormalizedPrefix) {
                    if ($groupsNormalizedPrefix) {
                        $inner->where('name_normalized', 'like', $groupsNormalizedPrefix);
                    }

                    if ($groupsPrefix) {
                        if ($groupsNormalizedPrefix) {
                            $inner->orWhere('code', 'like', $groupsPrefix);
                        } else {
                            $inner->where('code', 'like', $groupsPrefix);
                        }
                    }
                });
            })
            ->withCount([
                'memberships as active_members_count' => function ($q) {
                    $this->activeTodayConstraint($q);
                },
                'leaderships as active_leaders_count' => function ($q) {
                    $this->activeTodayConstraint($q);
                },
            ])
            ->when($groupsStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($groupsStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($groupsStatus === 'has_leaders', function ($q) {
                $q->whereHas('leaderships', function ($inner) {
                    $this->activeTodayConstraint($inner);
                });
            })
            ->when($groupsStatus === 'no_leaders', function ($q) {
                $q->whereDoesntHave('leaderships', function ($inner) {
                    $this->activeTodayConstraint($inner);
                });
            })
            ->when($groupsSort === 'members', fn ($q) => $q->orderByDesc('active_members_count')->orderBy('name_normalized'))
            ->when($groupsSort === 'leaders', fn ($q) => $q->orderByDesc('active_leaders_count')->orderBy('name_normalized'))
            ->when($groupsSort === 'order', fn ($q) => $q->orderBy('sort_order')->orderBy('name_normalized'))
            ->when(! in_array($groupsSort, ['members', 'leaders', 'order'], true), fn ($q) => $q->orderBy('name_normalized'));
    }

    private function activeLeaderRoles(int $parishId)
    {
        return ParishAssociationLeaderRole::query()
            ->where('parish_id', $parishId)
            ->where('is_active', true)
            ->withCount([
                'leaderships as active_assignments_count' => function ($q) {
                    $this->activeTodayConstraint($q);
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name_normalized')
            ->get(['uuid', 'name', 'sort_order', 'is_active']);
    }

    private function activeOutstations(int $parishId)
    {
        return Outstation::query()
            ->where('parish_id', $parishId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['uuid', 'name']);
    }

    private function permissionsPayload(Request $request): array
    {
        return [
            'viewAllAssociations' => $request->user()?->can(self::VIEW_ALL_PERMISSION) ?? false,
            'viewMembers' => $request->user()?->can(self::MEMBER_VIEW_PERMISSION) ?? false,
            'viewLeadership' => $request->user()?->can(self::LEADERSHIP_VIEW_PERMISSION) ?? false,
            'viewLeaderRoles' => $this->canReadLeaderRoles($request),
            'createAssociation' => $request->user()?->can(self::CREATE_PERMISSION) ?? false,
            'updateAssociation' => $request->user()?->can(self::UPDATE_PERMISSION) ?? false,
            'deleteAssociation' => $request->user()?->can(self::DELETE_PERMISSION) ?? false,
            'manageMembers' => (
                ($request->user()?->can(self::MEMBER_CREATE_PERMISSION) ?? false)
                || ($request->user()?->can(self::MEMBER_UPDATE_PERMISSION) ?? false)
                || ($request->user()?->can(self::MEMBER_DELETE_PERMISSION) ?? false)
            ),
            'manageLeadership' => (
                ($request->user()?->can(self::LEADERSHIP_CREATE_PERMISSION) ?? false)
                || ($request->user()?->can(self::LEADERSHIP_UPDATE_PERMISSION) ?? false)
                || ($request->user()?->can(self::LEADERSHIP_DELETE_PERMISSION) ?? false)
            ),
            'manageLeaderRoles' => (
                ($request->user()?->can(self::LEADER_ROLE_CREATE_PERMISSION) ?? false)
                || ($request->user()?->can(self::LEADER_ROLE_UPDATE_PERMISSION) ?? false)
                || ($request->user()?->can(self::LEADER_ROLE_DELETE_PERMISSION) ?? false)
            ),
        ];
    }

    private function membershipsQuery(ParishAssociation $association, ?string $membersQuery, int $selectedOutstationId): \Illuminate\Database\Eloquent\Builder
    {
        return ParishAssociationMember::query()
            ->with([
                'member:id,uuid,jumuiya_id,first_name,middle_name,last_name,gender,phone,email,is_active',
                'member.jumuiya:id,uuid,name,zone_id',
                'member.jumuiya.zone:id,uuid,name,outstation_id',
                'member.jumuiya.zone.outstation:id,uuid,name',
            ])
            ->where('parish_association_id', $association->id)
            ->when($membersQuery, function ($query) use ($membersQuery) {
                $query->whereHas('member', function ($memberQuery) use ($membersQuery) {
                    $this->applyMemberSearchConstraint($memberQuery, $membersQuery);
                });
            })
            ->when($selectedOutstationId > 0, function ($query) use ($selectedOutstationId) {
                $query->whereHas('member.jumuiya.zone', fn ($zone) => $zone->where('outstation_id', $selectedOutstationId));
            })
            ->orderByDesc('is_active')
            ->orderBy('joined_at')
            ->orderBy('member_id');
    }

    private function leadershipsQuery(
        ParishAssociation $association,
        ?string $leadershipQuery,
        ?string $leadershipRoleNormalizedPrefix,
        int $selectedOutstationId
    ): \Illuminate\Database\Eloquent\Builder
    {
        return ParishAssociationLeadership::query()
            ->with([
                'member:id,uuid,jumuiya_id,first_name,middle_name,last_name,gender,phone,email',
                'member.jumuiya:id,uuid,name,zone_id',
                'member.jumuiya.zone:id,uuid,name,outstation_id',
                'member.jumuiya.zone.outstation:id,uuid,name',
                'role:id,uuid,name',
            ])
            ->where('parish_association_id', $association->id)
            ->when($leadershipQuery || $leadershipRoleNormalizedPrefix, function ($query) use ($leadershipQuery, $leadershipRoleNormalizedPrefix) {
                $query->where(function ($inner) use ($leadershipQuery, $leadershipRoleNormalizedPrefix) {
                    if ($leadershipQuery) {
                        $inner->whereHas('member', function ($memberQuery) use ($leadershipQuery) {
                            $this->applyMemberSearchConstraint($memberQuery, $leadershipQuery);
                        });
                    }

                    if ($leadershipRoleNormalizedPrefix) {
                        if ($leadershipQuery) {
                            $inner->orWhereHas('role', fn ($roleQuery) => $roleQuery->where('name_normalized', 'like', $leadershipRoleNormalizedPrefix));
                        } else {
                            $inner->whereHas('role', fn ($roleQuery) => $roleQuery->where('name_normalized', 'like', $leadershipRoleNormalizedPrefix));
                        }
                    }
                });
            })
            ->when($selectedOutstationId > 0, function ($query) use ($selectedOutstationId) {
                $query->whereHas('member.jumuiya.zone', fn ($zone) => $zone->where('outstation_id', $selectedOutstationId));
            })
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->orderByDesc('id');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ParishAssociation::class);

        $parishId = $this->resolveCurrentParishId($request->user());
        $validated = $request->validate([
            'outstation_uuid' => ['nullable', 'uuid'],
            'active_only' => ['nullable', 'boolean'],
            'groups_q' => ['nullable', 'string', 'max:120'],
            'groups_status' => ['nullable', 'in:all,active,inactive,has_leaders,no_leaders'],
            'groups_sort' => ['nullable', 'in:name,members,leaders,order'],
            'groups_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $selectedOutstationUuid = is_string($validated['outstation_uuid'] ?? null) ? $validated['outstation_uuid'] : '';
        $activeOnly = filter_var($validated['active_only'] ?? false, FILTER_VALIDATE_BOOL);
        $groupsQuery = is_string($validated['groups_q'] ?? null) ? trim((string) $validated['groups_q']) : '';
        $groupsStatus = is_string($validated['groups_status'] ?? null) ? trim((string) $validated['groups_status']) : 'all';
        $groupsSort = is_string($validated['groups_sort'] ?? null) ? trim((string) $validated['groups_sort']) : 'name';
        $groupsPrefix = $this->escapedPrefixLike($groupsQuery);
        $groupsNormalizedPrefix = $this->normalizedPrefixLike($groupsQuery);
        $associations = $this->associationsQuery($request, $parishId, $activeOnly, $groupsPrefix, $groupsNormalizedPrefix, $groupsStatus, $groupsSort)
            ->simplePaginate(25, ['*'], 'groups_page')
            ->withQueryString();

        return Inertia::render('Kitume/Index', [
            'filters' => [
                'outstation_uuid' => $selectedOutstationUuid !== '' ? $selectedOutstationUuid : null,
                'active_only' => $activeOnly,
                'groups_q' => $groupsQuery !== '' ? $groupsQuery : null,
                'groups_status' => $groupsStatus,
                'groups_sort' => $groupsSort,
            ],
            'can' => $this->permissionsPayload($request),
            'outstations' => $this->activeOutstations($parishId),
            'associations' => ParishAssociationResource::collection($associations),
        ]);
    }

    public function members(Request $request, ParishAssociation $parishAssociation): Response
    {
        $this->authorize('viewAny', ParishAssociationMember::class);

        $parishId = $this->resolveCurrentParishId($request->user());
        $association = $this->visibleAssociationOrFail($request, $parishAssociation->uuid, $parishId);
        $validated = $request->validate([
            'outstation_uuid' => ['nullable', 'uuid'],
            'members_q' => ['nullable', 'string', 'max:120'],
            'members_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $selectedOutstationUuid = is_string($validated['outstation_uuid'] ?? null) ? $validated['outstation_uuid'] : '';
        $membersQuery = is_string($validated['members_q'] ?? null) ? trim((string) $validated['members_q']) : '';
        $selectedOutstationId = (int) ($this->resolveOutstationIdForParish($parishId, $selectedOutstationUuid) ?? 0);

        $memberships = $this->membershipsQuery($association, $membersQuery, $selectedOutstationId)
            ->simplePaginate(25, ['*'], 'members_page')
            ->withQueryString();

        return Inertia::render('Kitume/Members', [
            'association' => (new ParishAssociationResource($association))->resolve(),
            'filters' => [
                'outstation_uuid' => $selectedOutstationUuid !== '' ? $selectedOutstationUuid : null,
                'members_q' => $membersQuery !== '' ? $membersQuery : null,
            ],
            'can' => $this->permissionsPayload($request),
            'outstations' => $this->activeOutstations($parishId),
            'memberships' => ParishAssociationMemberResource::collection($memberships),
        ]);
    }

    public function leaders(Request $request, ParishAssociation $parishAssociation): Response
    {
        $this->authorize('viewAny', ParishAssociationLeadership::class);

        $parishId = $this->resolveCurrentParishId($request->user());
        $association = $this->visibleAssociationOrFail($request, $parishAssociation->uuid, $parishId);
        $validated = $request->validate([
            'outstation_uuid' => ['nullable', 'uuid'],
            'leadership_q' => ['nullable', 'string', 'max:120'],
            'leadership_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $selectedOutstationUuid = is_string($validated['outstation_uuid'] ?? null) ? $validated['outstation_uuid'] : '';
        $leadershipQuery = is_string($validated['leadership_q'] ?? null) ? trim((string) $validated['leadership_q']) : '';
        $leadershipRoleNormalizedPrefix = $this->normalizedPrefixLike($leadershipQuery);
        $selectedOutstationId = (int) ($this->resolveOutstationIdForParish($parishId, $selectedOutstationUuid) ?? 0);

        $leaderships = $this->leadershipsQuery($association, $leadershipQuery, $leadershipRoleNormalizedPrefix, $selectedOutstationId)
            ->simplePaginate(25, ['*'], 'leadership_page')
            ->withQueryString();

        return Inertia::render('Kitume/Leaders', [
            'association' => (new ParishAssociationResource($association))->resolve(),
            'filters' => [
                'outstation_uuid' => $selectedOutstationUuid !== '' ? $selectedOutstationUuid : null,
                'leadership_q' => $leadershipQuery !== '' ? $leadershipQuery : null,
            ],
            'can' => $this->permissionsPayload($request),
            'outstations' => $this->activeOutstations($parishId),
            'leaderRoles' => $this->canReadLeaderRoles($request) ? $this->activeLeaderRoles($parishId) : [],
            'leaderships' => ParishAssociationLeadershipResource::collection($leaderships),
        ]);
    }

    public function leaderRoles(Request $request, ParishAssociation $parishAssociation): Response
    {
        abort_unless($this->canReadLeaderRoles($request), 403);

        $parishId = $this->resolveCurrentParishId($request->user());
        $association = $this->visibleAssociationOrFail($request, $parishAssociation->uuid, $parishId);
        $validated = $request->validate([
            'roles_q' => ['nullable', 'string', 'max:120'],
            'roles_status' => ['nullable', 'in:all,active,inactive'],
            'roles_page' => ['nullable', 'integer', 'min:1'],
        ]);

        $rolesQuery = is_string($validated['roles_q'] ?? null) ? trim((string) $validated['roles_q']) : '';
        $rolesStatus = is_string($validated['roles_status'] ?? null) ? trim((string) $validated['roles_status']) : 'all';
        $rolesNormalizedPrefix = $this->normalizedPrefixLike($rolesQuery);

        $roles = ParishAssociationLeaderRole::query()
            ->where('parish_id', $parishId)
            ->withCount([
                'leaderships as active_assignments_count' => function ($q) {
                    $this->activeTodayConstraint($q);
                },
            ])
            ->when($rolesNormalizedPrefix, fn ($q) => $q->where('name_normalized', 'like', $rolesNormalizedPrefix))
            ->when($rolesStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($rolesStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name_normalized')
            ->simplePaginate(25, ['id', 'uuid', 'name', 'sort_order', 'is_active'], 'roles_page')
            ->withQueryString()
            ->through(function (ParishAssociationLeaderRole $role): array {
                return [
                    'uuid' => $role->uuid,
                    'name' => $role->name,
                    'sort_order' => $role->sort_order,
                    'is_active' => (bool) $role->is_active,
                    'active_assignments_count' => (int) ($role->active_assignments_count ?? 0),
                ];
            });

        return Inertia::render('Kitume/LeaderRoles', [
            'association' => (new ParishAssociationResource($association))->resolve(),
            'filters' => [
                'roles_q' => $rolesQuery !== '' ? $rolesQuery : null,
                'roles_status' => $rolesStatus,
            ],
            'can' => $this->permissionsPayload($request),
            'leaderRoles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ParishAssociation::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $parishId = $this->resolveCurrentParishId($request->user());
        $normalizedName = mb_strtolower((string) (NormalizesNames::normalize((string) $validated['name']) ?? ''), 'UTF-8');

        $exists = ParishAssociation::query()
            ->where('parish_id', $parishId)
            ->where('name_normalized', $normalizedName)
            ->exists();

        if ($exists) {
            return back()->with('error', 'A kitume group with this name already exists in this parish.');
        }

        try {
            ParishAssociation::query()->create([
                'parish_id' => $parishId,
                'name' => trim($validated['name']),
                'code' => isset($validated['code']) ? trim((string) $validated['code']) ?: null : null,
                'description' => isset($validated['description']) ? trim((string) $validated['description']) ?: null : null,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            ]);

            return back()->with('success', 'Kitume group created.');
        } catch (\Throwable $e) {
            Log::error('Parish association create failed', ['exception' => $e]);
            return back()->with('error', 'Unable to create kitume group.');
        }
    }

    public function update(Request $request, ParishAssociation $parishAssociation): RedirectResponse
    {
        $association = $this->associationOrFail($parishAssociation->uuid, $this->resolveCurrentParishId($request->user()));
        $this->authorize('update', $association);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $normalizedName = mb_strtolower((string) (NormalizesNames::normalize((string) $validated['name']) ?? ''), 'UTF-8');
        $exists = ParishAssociation::query()
            ->where('parish_id', $association->parish_id)
            ->where('id', '!=', $association->id)
            ->where('name_normalized', $normalizedName)
            ->exists();

        if ($exists) {
            return back()->with('error', 'A kitume group with this name already exists in this parish.');
        }

        try {
            $association->update([
                'name' => trim($validated['name']),
                'code' => isset($validated['code']) ? trim((string) $validated['code']) ?: null : null,
                'description' => isset($validated['description']) ? trim((string) $validated['description']) ?: null : null,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $association->is_active,
            ]);

            return back()->with('success', 'Kitume group updated.');
        } catch (\Throwable $e) {
            Log::error('Parish association update failed', ['exception' => $e, 'association_uuid' => $association->uuid]);
            return back()->with('error', 'Unable to update kitume group.');
        }
    }

    public function destroy(Request $request, ParishAssociation $parishAssociation): RedirectResponse
    {
        $association = $this->associationOrFail($parishAssociation->uuid, $this->resolveCurrentParishId($request->user()));
        $this->authorize('delete', $association);

        if ($association->memberships()->exists() || $association->leaderships()->exists()) {
            return back()->with('error', 'Unable to delete. This group already has members or leaders.');
        }

        try {
            $association->delete();
            return back()->with('success', 'Kitume group deleted.');
        } catch (\Throwable $e) {
            Log::error('Parish association delete failed', ['exception' => $e, 'association_uuid' => $association->uuid]);
            return back()->with('error', 'Unable to delete kitume group.');
        }
    }

    public function storeMember(Request $request, ParishAssociation $parishAssociation): RedirectResponse
    {
        $this->authorize('create', ParishAssociationMember::class);

        $association = $this->associationOrFail($parishAssociation->uuid, $this->resolveCurrentParishId($request->user()));

        $validated = $request->validate([
            'member_uuid' => ['required', 'uuid'],
            'joined_at' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:joined_at'],
            'notes' => ['nullable', 'string', 'max:1500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $member = $this->parishMemberQuery($association->parish_id)
            ->where('uuid', $validated['member_uuid'])
            ->first();

        if (! $member) {
            return back()->with('error', 'Invalid parish member selected.');
        }

        try {
            ParishAssociationMember::query()->updateOrCreate(
                [
                    'parish_association_id' => $association->id,
                    'member_id' => $member->id,
                ],
                [
                    'joined_at' => $validated['joined_at'] ?? now()->toDateString(),
                    'end_date' => $validated['end_date'] ?? null,
                    'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) ?: null : null,
                    'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
                ],
            );

            return back()->with('success', 'Member added to kitume group.');
        } catch (\Throwable $e) {
            Log::error('Parish association member store failed', ['exception' => $e, 'association_uuid' => $association->uuid]);
            return back()->with('error', 'Unable to add member to kitume group.');
        }
    }

    public function updateMember(Request $request, ParishAssociationMember $membership): RedirectResponse
    {
        $record = ParishAssociationMember::query()
            ->with('association')
            ->where('uuid', $membership->uuid)
            ->firstOrFail();

        abort_unless((int) $record->association?->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('update', $record);

        $validated = $request->validate([
            'joined_at' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:joined_at'],
            'notes' => ['nullable', 'string', 'max:1500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $record->update([
                'joined_at' => $validated['joined_at'] ?? $record->joined_at?->format('Y-m-d'),
                'end_date' => $validated['end_date'] ?? null,
                'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) ?: null : null,
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $record->is_active,
            ]);

            return back()->with('success', 'Membership updated.');
        } catch (\Throwable $e) {
            Log::error('Parish association member update failed', ['exception' => $e, 'membership_uuid' => $record->uuid]);
            return back()->with('error', 'Unable to update membership.');
        }
    }

    public function destroyMember(Request $request, ParishAssociationMember $membership): RedirectResponse
    {
        $record = ParishAssociationMember::query()
            ->with('association')
            ->where('uuid', $membership->uuid)
            ->firstOrFail();

        abort_unless((int) $record->association?->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('delete', $record);

        $hasLeadership = ParishAssociationLeadership::query()
            ->where('parish_association_id', $record->parish_association_id)
            ->where('member_id', $record->member_id)
            ->exists();

        if ($hasLeadership) {
            return back()->with('error', 'Unable to delete membership. End leadership records first.');
        }

        try {
            $record->delete();
            return back()->with('success', 'Membership deleted.');
        } catch (\Throwable $e) {
            Log::error('Parish association member delete failed', ['exception' => $e, 'membership_uuid' => $record->uuid]);
            return back()->with('error', 'Unable to delete membership.');
        }
    }

    public function storeLeadership(Request $request, ParishAssociation $parishAssociation): RedirectResponse
    {
        $this->authorize('create', ParishAssociationLeadership::class);

        $association = $this->associationOrFail($parishAssociation->uuid, $this->resolveCurrentParishId($request->user()));

        $validated = $request->validate([
            'member_uuid' => ['required', 'uuid'],
            'role_uuid' => ['required', 'uuid'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $member = $this->parishMemberQuery($association->parish_id)
            ->where('uuid', $validated['member_uuid'])
            ->first();

        if (! $member) {
            return back()->with('error', 'Invalid parish member selected.');
        }

        $role = ParishAssociationLeaderRole::query()
            ->where('parish_id', $association->parish_id)
            ->where('uuid', $validated['role_uuid'])
            ->where('is_active', true)
            ->first();

        if (! $role) {
            return back()->with('error', 'Invalid leadership role selected.');
        }

        $isActive = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true;
        $effectiveEndDate = $validated['end_date'] ?? null;
        $effectiveActive = $isActive && (! $effectiveEndDate || Carbon::parse($effectiveEndDate)->greaterThanOrEqualTo(now()->startOfDay()));

        if ($effectiveActive) {
            $roleTaken = ParishAssociationLeadership::query()
                ->where('parish_association_id', $association->id)
                ->where('parish_association_leader_role_id', $role->id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
                })
                ->exists();

            if ($roleTaken) {
                return back()->with('error', 'This leadership role is already assigned in the selected group.');
            }

            $memberHasActiveRole = ParishAssociationLeadership::query()
                ->where('parish_association_id', $association->id)
                ->where('member_id', $member->id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
                })
                ->exists();

            if ($memberHasActiveRole) {
                return back()->with('error', 'This member already has an active leadership role in this group.');
            }
        }

        try {
            $tempPassword = null;

            DB::transaction(function () use ($association, $member, $role, $validated, $isActive, &$tempPassword): void {
                ParishAssociationMember::query()->updateOrCreate(
                    [
                        'parish_association_id' => $association->id,
                        'member_id' => $member->id,
                    ],
                    [
                        'joined_at' => $validated['start_date'],
                        'end_date' => null,
                        'is_active' => true,
                    ],
                );

                ParishAssociationLeadership::query()->create([
                    'parish_association_id' => $association->id,
                    'member_id' => $member->id,
                    'parish_association_leader_role_id' => $role->id,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'is_active' => $isActive,
                ]);

                if ($isActive) {
                    $tempPassword = $this->provisionMemberLoginAccess(
                        $member,
                        (int) $association->parish_id,
                        self::LEADER_LOGIN_PERMISSIONS,
                        'member'
                    );
                }
            });

            if (is_string($tempPassword) && $tempPassword !== '') {
                return back()->with('success', 'Leader assigned. Login enabled. Temporary password: '.$tempPassword);
            }

            return back()->with('success', 'Leader assigned to kitume group.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Parish association leadership store failed', ['exception' => $e, 'association_uuid' => $association->uuid]);
            return back()->with('error', 'Unable to assign leader.');
        }
    }

    public function storeLeaderRole(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can(self::LEADER_ROLE_CREATE_PERMISSION), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $parishId = $this->resolveCurrentParishId($request->user());
        $normalizedName = mb_strtolower((string) (NormalizesNames::normalize((string) $validated['name']) ?? ''), 'UTF-8');

        $exists = ParishAssociationLeaderRole::query()
            ->where('parish_id', $parishId)
            ->where('name_normalized', $normalizedName)
            ->exists();

        if ($exists) {
            return back()->with('error', 'A leader position with this name already exists in this parish.');
        }

        try {
            ParishAssociationLeaderRole::query()->create([
                'parish_id' => $parishId,
                'name' => trim((string) $validated['name']),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            ]);

            return back()->with('success', 'Leader position created.');
        } catch (\Throwable $e) {
            Log::error('Parish association leader role create failed', ['exception' => $e]);
            return back()->with('error', 'Unable to create leader position.');
        }
    }

    public function updateLeadership(Request $request, ParishAssociationLeadership $leadership): RedirectResponse
    {
        $record = ParishAssociationLeadership::query()
            ->with('association')
            ->where('uuid', $leadership->uuid)
            ->firstOrFail();

        abort_unless((int) $record->association?->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('update', $record);

        $validated = $request->validate([
            'role_uuid' => ['required', 'uuid'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = ParishAssociationLeaderRole::query()
            ->where('parish_id', $record->association->parish_id)
            ->where('uuid', $validated['role_uuid'])
            ->where('is_active', true)
            ->first();

        if (! $role) {
            return back()->with('error', 'Invalid leadership role selected.');
        }

        $isActive = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $record->is_active;
        $effectiveEndDate = $validated['end_date'] ?? null;
        $effectiveActive = $isActive && (! $effectiveEndDate || Carbon::parse($effectiveEndDate)->greaterThanOrEqualTo(now()->startOfDay()));

        if ($effectiveActive) {
            $roleTaken = ParishAssociationLeadership::query()
                ->where('parish_association_id', $record->parish_association_id)
                ->where('parish_association_leader_role_id', $role->id)
                ->where('id', '!=', $record->id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
                })
                ->exists();

            if ($roleTaken) {
                return back()->with('error', 'This leadership role is already assigned in the selected group.');
            }

            $memberHasActiveRole = ParishAssociationLeadership::query()
                ->where('parish_association_id', $record->parish_association_id)
                ->where('member_id', $record->member_id)
                ->where('id', '!=', $record->id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
                })
                ->exists();

            if ($memberHasActiveRole) {
                return back()->with('error', 'This member already has another active leadership role in this group.');
            }
        }

        try {
            $tempPassword = null;

            DB::transaction(function () use ($record, $role, $validated, $isActive, &$tempPassword): void {
                $record->update([
                    'parish_association_leader_role_id' => $role->id,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'] ?? null,
                    'is_active' => $isActive,
                ]);

                $record->loadMissing('member');
                $member = $record->member;
                if (! $member) {
                    return;
                }

                if ($this->memberHasActiveKitumeLeadership((int) $member->id)) {
                    $tempPassword = $this->provisionMemberLoginAccess(
                        $member,
                        (int) $record->association->parish_id,
                        self::LEADER_LOGIN_PERMISSIONS,
                        'member'
                    );
                    return;
                }

                $this->syncMemberDirectPermissions(
                    $member,
                    self::LEADER_LOGIN_PERMISSIONS,
                    false
                );
            });

            if (is_string($tempPassword) && $tempPassword !== '') {
                return back()->with('success', 'Leadership updated. Login enabled. Temporary password: '.$tempPassword);
            }

            return back()->with('success', 'Leadership updated.');
        } catch (\Throwable $e) {
            Log::error('Parish association leadership update failed', ['exception' => $e, 'leadership_uuid' => $record->uuid]);
            return back()->with('error', 'Unable to update leadership.');
        }
    }

    public function updateLeaderRole(Request $request, ParishAssociationLeaderRole $role): RedirectResponse
    {
        abort_unless((int) $role->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        abort_unless($request->user()?->can(self::LEADER_ROLE_UPDATE_PERMISSION), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $normalizedName = mb_strtolower((string) (NormalizesNames::normalize((string) $validated['name']) ?? ''), 'UTF-8');
        $exists = ParishAssociationLeaderRole::query()
            ->where('parish_id', $role->parish_id)
            ->where('id', '!=', $role->id)
            ->where('name_normalized', $normalizedName)
            ->exists();

        if ($exists) {
            return back()->with('error', 'A leader position with this name already exists in this parish.');
        }

        try {
            $role->update([
                'name' => trim((string) $validated['name']),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $role->is_active,
            ]);

            return back()->with('success', 'Leader position updated.');
        } catch (\Throwable $e) {
            Log::error('Parish association leader role update failed', ['exception' => $e, 'role_uuid' => $role->uuid]);
            return back()->with('error', 'Unable to update leader position.');
        }
    }

    public function destroyLeadership(Request $request, ParishAssociationLeadership $leadership): RedirectResponse
    {
        $record = ParishAssociationLeadership::query()
            ->with(['association', 'member'])
            ->where('uuid', $leadership->uuid)
            ->firstOrFail();

        abort_unless((int) $record->association?->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('delete', $record);

        if ($record->is_active && (! $record->end_date || $record->end_date->greaterThanOrEqualTo(now()->startOfDay()))) {
            return back()->with('error', 'Unable to delete active leadership. End it first.');
        }

        try {
            $memberId = (int) $record->member_id;
            $record->delete();

            if ($memberId > 0) {
                $member = $record->member;
                if ($member) {
                    $this->syncMemberDirectPermissions(
                        $member,
                        self::LEADER_LOGIN_PERMISSIONS,
                        $this->memberHasActiveKitumeLeadership($memberId)
                    );
                }
            }

            return back()->with('success', 'Leadership deleted.');
        } catch (\Throwable $e) {
            Log::error('Parish association leadership delete failed', ['exception' => $e, 'leadership_uuid' => $record->uuid]);
            return back()->with('error', 'Unable to delete leadership.');
        }
    }

    public function destroyLeaderRole(Request $request, ParishAssociationLeaderRole $role): RedirectResponse
    {
        abort_unless((int) $role->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        abort_unless($request->user()?->can(self::LEADER_ROLE_DELETE_PERMISSION), 403);

        if ($role->leaderships()->exists()) {
            return back()->with('error', 'Unable to delete leader position. It is already used in leadership records.');
        }

        try {
            $role->delete();
            return back()->with('success', 'Leader position deleted.');
        } catch (\Throwable $e) {
            Log::error('Parish association leader role delete failed', ['exception' => $e, 'role_uuid' => $role->uuid]);
            return back()->with('error', 'Unable to delete leader position.');
        }
    }

    private function memberHasActiveKitumeLeadership(int $memberId): bool
    {
        return ParishAssociationLeadership::query()
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->exists();
    }
}
