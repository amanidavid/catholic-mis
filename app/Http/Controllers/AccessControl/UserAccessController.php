<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';
        $safe = addcslashes($q, '%_\\');

        $users = User::query()
            ->select(['id', 'uuid', 'name', 'email', 'is_active'])
            ->with([
                'roles:id,name',
                'permissions:id,name',
            ])
            ->when($q !== '', function (Builder $qb) use ($safe) {
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('name', 'like', $safe.'%')
                        ->orWhere('email', 'like', $safe.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $users->getCollection()->transform(function (User $user) {
            $roleNames = $user->roles->pluck('name')->values()->all();
            $directPermissionNames = $user->permissions->pluck('name')->values()->all();

            $user->setAttribute('role_names', $roleNames);
            $user->setAttribute('direct_permission_names', $directPermissionNames);
            $user->setAttribute(
                'effective_permissions_count',
                method_exists($user, 'getAllPermissions') ? $user->getAllPermissions()->count() : 0
            );

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
            return $user;
        });

        $roles = Role::query()
            ->select(['id', 'name'])
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->pluck('name')
            ->values();

        $permissions = Permission::query()
            ->select(['name', 'module', 'display_name'])
            ->where('guard_name', 'web')
            ->orderBy('module')
            ->orderBy('display_name')
            ->orderBy('name')
            ->get();

        return Inertia::render('AccessControl/Users/Index', [
            'filters' => [
                'q' => $q,
            ],
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function syncRoles(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'roles' => ['nullable', 'array', 'max:100'],
            'roles.*' => ['string', 'max:190'],
        ]);

        $names = collect($validated['roles'] ?? [])->filter()->unique()->values();

        $roles = $names->isEmpty()
            ? collect()
            : Role::query()->whereIn('name', $names)->where('guard_name', 'web')->get();

        $missing = $names->diff($roles->pluck('name'));
        if ($missing->isNotEmpty()) {
            return back()->with('error', 'Some roles were not found.');
        }

        $user->syncRoles($roles);

        return back()->with('success', 'User roles updated successfully.');
    }

    public function syncDirectPermissions(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array', 'max:500'],
            'permissions.*' => ['string', 'max:190'],
        ]);

        $names = collect($validated['permissions'] ?? [])->filter()->unique()->values();

        $permissions = $names->isEmpty()
            ? collect()
            : Permission::query()->whereIn('name', $names)->where('guard_name', 'web')->get();

        $missing = $names->diff($permissions->pluck('name'));
        if ($missing->isNotEmpty()) {
            return back()->with('error', 'Some permissions were not found.');
        }

        $user->syncPermissions($permissions);

        return back()->with('success', 'User direct permissions updated successfully.');
    }
}
