<?php

namespace App\Http\Controllers\Leadership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leadership\StoreJumuiyaLeadershipRequest;
use App\Http\Requests\Leadership\UpdateJumuiyaLeadershipRequest;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\Leadership\JumuiyaLeadershipRole;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use App\Models\User;
use App\Http\Resources\Leadership\JumuiyaLeadershipResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class JumuiyaLeadershipController extends Controller
{
    protected function scopedJumuiyaId(Request $request): ?int
    {
        if ($request->user()?->can('jumuiyas.view')) {
            return null;
        }

        return $request->user()?->member?->jumuiya_id;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', JumuiyaLeadership::class);

        $jumuiyaUuid = $request->query('jumuiya_uuid');
        $activeOnly = filter_var($request->query('active_only', false), FILTER_VALIDATE_BOOL);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        $query = JumuiyaLeadership::query()->with([
            'jumuiya:id,uuid,name',
            'member:id,uuid,first_name,middle_name,last_name,email',
            'role:id,uuid,name,system_role_name',
        ]);

        $selectedJumuiyaUuid = null;
        if (is_string($jumuiyaUuid) && $jumuiyaUuid !== '') {
            $selectedJumuiyaUuid = $jumuiyaUuid;
        }

        if ($scopedJumuiyaId) {
            $query->where('jumuiya_id', $scopedJumuiyaId);
            $selectedJumuiyaUuid = (string) Jumuiya::query()->where('id', $scopedJumuiyaId)->value('uuid');
        } elseif ($selectedJumuiyaUuid) {
            $selectedJumuiyaId = (int) Jumuiya::query()->where('uuid', $selectedJumuiyaUuid)->value('id');
            if ($selectedJumuiyaId) {
                $query->where('jumuiya_id', $selectedJumuiyaId);
            }
        }

        if ($activeOnly) {
            $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', Date::today());
                });
        }

        $leaderships = $query
            ->orderByDesc('start_date')
            ->get();

        if ($request->header('X-Inertia')) {
            $jumuiyas = [];
            if ($scopedJumuiyaId) {
                $jumuiyas = Jumuiya::query()
                    ->where('id', $scopedJumuiyaId)
                    ->orderBy('name')
                    ->get(['uuid', 'name']);
            } else {
                $jumuiyas = Jumuiya::query()
                    ->orderBy('name')
                    ->get(['uuid', 'name']);
            }

            $roles = JumuiyaLeadershipRole::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['uuid', 'name']);

            return Inertia::render('Leadership/Assignments/Index', [
                'filters' => [
                    'jumuiya_uuid' => $selectedJumuiyaUuid,
                    'active_only' => $activeOnly,
                ],
                'jumuiyas' => $jumuiyas,
                'roles' => $roles,
                'leaderships' => JumuiyaLeadershipResource::collection($leaderships),
            ]);
        }

        return JumuiyaLeadershipResource::collection($leaderships);
    }

    public function store(StoreJumuiyaLeadershipRequest $request): RedirectResponse
    {
        $this->authorize('create', JumuiyaLeadership::class);

        $validated = $request->validated();

        $jumuiya = Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->firstOrFail();
        $member = Member::query()->where('uuid', $validated['member_uuid'])->firstOrFail();
        $role = JumuiyaLeadershipRole::query()->where('uuid', $validated['role_uuid'])->firstOrFail();

        if (! $role->is_active) {
            return back()->with('error', 'Invalid leadership role.');
        }

        if ($member->jumuiya_id !== $jumuiya->id) {
            return back()->with('error', 'Invalid member selection for this Christian Community.');
        }

        $effectiveEndDate = $validated['end_date'] ?? null;
        $isActive = (bool) ($validated['is_active'] ?? true);
        $isEffectiveActive = $isActive && (! $effectiveEndDate || Date::parse($effectiveEndDate)->greaterThanOrEqualTo(Date::today()));

        if ($isEffectiveActive) {
            $hasOtherActiveLeadership = JumuiyaLeadership::query()
                ->where('member_id', $member->id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', Date::today());
                })
                ->exists();

            if ($hasOtherActiveLeadership) {
                return back()->with('error', 'This member already has an active leadership role. End the current role first.');
            }

            $roleAlreadyTaken = JumuiyaLeadership::query()
                ->where('jumuiya_id', $jumuiya->id)
                ->where('jumuiya_leadership_role_id', $role->id)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', Date::today());
                })
                ->exists();

            if ($roleAlreadyTaken) {
                return back()->with('error', 'This leadership role is already assigned to another member. End the current assignment first.');
            }
        }

        try {
            $leadership = JumuiyaLeadership::create([
                'jumuiya_id' => $jumuiya->id,
                'member_id' => $member->id,
                'jumuiya_leadership_role_id' => $role->id,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => $isActive,
            ]);

            $tempPassword = null;
            if (! empty($validated['create_login'])) {
                $tempPassword = $this->provisionLeaderAccount($member, $role->system_role_name ?? null);
            }

            if ($tempPassword) {
                return back()->with('success', 'Leadership assigned. Account created. Temporary password: '.$tempPassword);
            }

            return back()->with('success', 'Leadership assigned.');
        } catch (\Throwable $e) {
            Log::error('Jumuiya leadership assign failed', [
                'exception' => $e,
                'jumuiya_uuid' => $validated['jumuiya_uuid'] ?? null,
                'member_uuid' => $validated['member_uuid'] ?? null,
                'role_uuid' => $validated['role_uuid'] ?? null,
            ]);

            return back()->with('error', 'Unable to assign leadership. Please try again.');
        }
    }

    public function createLogin(Request $request, JumuiyaLeadership $jumuiyaLeadership): RedirectResponse
    {
        $this->authorize('update', $jumuiyaLeadership);

        $member = $jumuiyaLeadership->member()->first();
        if (! $member) {
            return back()->with('error', 'Invalid member record.');
        }

        try {
            $role = $jumuiyaLeadership->role()->first();
            $tempPassword = $this->provisionLeaderAccount($member, $role?->system_role_name);

            if ($tempPassword) {
                return back()->with('success', 'Login enabled. Temporary password: '.$tempPassword.' (user will be forced to change password at first login)');
            }

            return back()->with('success', 'Login enabled.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Leadership login provision failed', ['exception' => $e, 'leadership_uuid' => $jumuiyaLeadership->uuid]);
            return back()->with('error', 'Unable to enable login. Please try again.');
        }
    }

    public function disableLogin(Request $request, JumuiyaLeadership $jumuiyaLeadership): RedirectResponse
    {
        $this->authorize('update', $jumuiyaLeadership);

        try {
            $user = User::query()->where('member_id', (int) $jumuiyaLeadership->member_id)->first();
            if (! $user) {
                return back()->with('error', 'No login account found for this member.');
            }

            $user->forceFill(['is_active' => false])->save();

            return back()->with('success', 'Login disabled.');
        } catch (\Throwable $e) {
            Log::error('Leadership login disable failed', ['exception' => $e, 'leadership_uuid' => $jumuiyaLeadership->uuid]);
            return back()->with('error', 'Unable to disable login. Please try again.');
        }
    }

    public function destroy(JumuiyaLeadership $jumuiyaLeadership): RedirectResponse
    {
        $this->authorize('delete', $jumuiyaLeadership);

        try {
            if ($jumuiyaLeadership->is_active && (! $jumuiyaLeadership->end_date || Date::parse($jumuiyaLeadership->end_date)->greaterThanOrEqualTo(Date::today()))) {
                return back()->with('error', 'Unable to delete an active leadership. End it first.');
            }

            $memberId = (int) $jumuiyaLeadership->member_id;
            $jumuiyaLeadership->delete();

            $this->syncLeaderLoginStatus($memberId);

            return back()->with('success', 'Leadership deleted.');
        } catch (\Throwable $e) {
            Log::error('Jumuiya leadership delete failed', [
                'exception' => $e,
                'leadership_uuid' => $jumuiyaLeadership->uuid,
            ]);

            return back()->with('error', 'Unable to delete leadership. Please try again.');
        }
    }

    public function update(UpdateJumuiyaLeadershipRequest $request, JumuiyaLeadership $jumuiyaLeadership): RedirectResponse
    {
        $this->authorize('update', $jumuiyaLeadership);

        $validated = $request->validated();

        try {
            $nextStartDate = array_key_exists('start_date', $validated) ? $validated['start_date'] : ($jumuiyaLeadership->start_date?->format('Y-m-d'));
            $nextEndDate = array_key_exists('end_date', $validated) ? $validated['end_date'] : ($jumuiyaLeadership->end_date?->format('Y-m-d'));
            $nextIsActive = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : (bool) $jumuiyaLeadership->is_active;

            $nextRoleId = (int) $jumuiyaLeadership->jumuiya_leadership_role_id;
            $nextRoleSystemName = $jumuiyaLeadership->role?->system_role_name;
            if (! empty($validated['role_uuid'])) {
                $role = JumuiyaLeadershipRole::query()->where('uuid', $validated['role_uuid'])->firstOrFail();
                if (! $role->is_active) {
                    return back()->with('error', 'Invalid leadership role.');
                }
                $nextRoleId = (int) $role->id;
                $nextRoleSystemName = $role->system_role_name;
            }

            if ($nextStartDate && $nextEndDate && Date::parse($nextEndDate)->lessThan(Date::parse($nextStartDate))) {
                return back()->with('error', 'End date cannot be before start date.');
            }

            if ($nextStartDate && Date::parse($nextStartDate)->isFuture() && $nextIsActive) {
                return back()->with('error', 'Start date cannot be in the future for an active assignment.');
            }

            $isEffectiveActive = $nextIsActive && (! $nextEndDate || Date::parse($nextEndDate)->greaterThanOrEqualTo(Date::today()));

            if ($isEffectiveActive) {
                $hasOtherActiveLeadership = JumuiyaLeadership::query()
                    ->where('member_id', $jumuiyaLeadership->member_id)
                    ->where('id', '!=', $jumuiyaLeadership->id)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', Date::today());
                    })
                    ->exists();

                if ($hasOtherActiveLeadership) {
                    return back()->with('error', 'This member already has another active leadership role. End it first.');
                }

                $roleAlreadyTaken = JumuiyaLeadership::query()
                    ->where('jumuiya_id', $jumuiyaLeadership->jumuiya_id)
                    ->where('jumuiya_leadership_role_id', $nextRoleId)
                    ->where('id', '!=', $jumuiyaLeadership->id)
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', Date::today());
                    })
                    ->exists();

                if ($roleAlreadyTaken) {
                    return back()->with('error', 'This leadership role is already assigned to another member. End the current assignment first.');
                }
            }

            DB::transaction(function () use ($jumuiyaLeadership, $nextStartDate, $nextEndDate, $nextIsActive, $nextRoleId, $nextRoleSystemName): void {
                $jumuiyaLeadership->start_date = $nextStartDate ? Date::parse($nextStartDate) : $jumuiyaLeadership->start_date;
                $jumuiyaLeadership->end_date = $nextEndDate ? Date::parse($nextEndDate) : null;
                $jumuiyaLeadership->is_active = $nextIsActive;
                $jumuiyaLeadership->jumuiya_leadership_role_id = $nextRoleId;

                if ($jumuiyaLeadership->end_date && Date::parse($jumuiyaLeadership->end_date)->isPast()) {
                    $jumuiyaLeadership->is_active = false;
                }

                $jumuiyaLeadership->save();

                $user = User::query()->where('member_id', (int) $jumuiyaLeadership->member_id)->first();
                if ($user && $nextRoleSystemName) {
                    Role::findOrCreate($nextRoleSystemName, 'web');
                    $user->assignRole($nextRoleSystemName);
                }
            });

            $this->syncLeaderLoginStatus($jumuiyaLeadership->member_id);

            return back()->with('success', 'Leadership updated.');
        } catch (\Throwable $e) {
            Log::error('Jumuiya leadership update failed', [
                'exception' => $e,
                'leadership_uuid' => $jumuiyaLeadership->uuid,
            ]);

            return back()->with('error', 'Unable to update leadership. Please try again.');
        }
    }

    private function provisionLeaderAccount(Member $member, ?string $systemRoleName): ?string
    {
        $email = trim((string) ($member->email ?? ''));
        if ($email === '') {
            throw new \RuntimeException('Member email is required to create a login account.');
        }

        $systemRoleName = $systemRoleName !== null ? trim($systemRoleName) : null;
        if ($systemRoleName === '') {
            $systemRoleName = null;
        }

        if ($systemRoleName) {
            Role::findOrCreate($systemRoleName, 'web');
        }

        $existingByMember = User::query()->where('member_id', $member->id)->first();
        if ($existingByMember) {
            $tempPassword = Str::password(12);

            $existingByMember->forceFill([
                'is_active' => true,
                'must_change_password' => true,
                'password' => Hash::make($tempPassword),
            ])->save();

            if ($systemRoleName) {
                $existingByMember->assignRole($systemRoleName);
            }

            return $tempPassword;
        }

        $existingByEmail = User::query()->where('email', $email)->first();
        if ($existingByEmail) {
            if ($existingByEmail->member_id && (int) $existingByEmail->member_id !== (int) $member->id) {
                throw new \RuntimeException('This email is already used by another account.');
            }

            $tempPassword = Str::password(12);

            $existingByEmail->forceFill([
                'member_id' => $member->id,
                'is_active' => true,
                'must_change_password' => true,
                'password' => Hash::make($tempPassword),
            ])->save();

            if ($systemRoleName) {
                $existingByEmail->assignRole($systemRoleName);
            }

            return $tempPassword;
        }

        $tempPassword = Str::password(12);

        $fullName = trim(implode(' ', array_filter([
            $member->first_name,
            $member->middle_name,
            $member->last_name,
        ])));

        $user = User::create([
            'name' => $fullName !== '' ? $fullName : $email,
            'email' => $email,
            'password' => Hash::make($tempPassword),
            'member_id' => $member->id,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        if ($systemRoleName) {
            $user->assignRole($systemRoleName);
        }

        return $tempPassword;
    }

    private function syncLeaderLoginStatus(int $memberId): void
    {
        $user = User::query()->where('member_id', $memberId)->first();
        if (! $user) {
            return;
        }

        $hasActiveLeadership = JumuiyaLeadership::query()
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', Date::today());
            })
            ->exists();

        if (! $hasActiveLeadership) {
            $user->forceFill(['is_active' => false])->save();
        }
    }
}
