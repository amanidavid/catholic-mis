<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        if (! Role::query()->exists()) {
            Role::findOrCreate('system-admin', 'web');
        }

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $safe = addcslashes($q, '%_\\');

        $roles = Role::query()
            ->with(['permissions:id,name'])
            ->withCount('permissions')
            ->when($q !== '', fn ($qb) => $qb->where('name', 'like', $safe.'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles->getCollection()->transform(function (Role $role) {
            $role->permissions = $role->permissions->pluck('name')->values()->all();
            return $role;
        });

        $permissions = Permission::query()
            ->select(['name', 'module', 'display_name'])
            ->orderBy('module')
            ->orderBy('display_name')
            ->get();

        return Inertia::render('AccessControl/Roles/Index', [
            'filters' => [
                'q' => $q,
            ],
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
        ]);

        $name = trim((string) $validated['name']);
        $regex = '/^[A-Za-z][A-Za-z0-9_.-]{2,63}$/';
        if (! preg_match($regex, $name)) {
            return back()->with('error', 'Invalid role name.');
        }

        $guard = 'web';
        $exists = Role::query()
            ->where('guard_name', $guard)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Role name already exists.');
        }

        Role::create([
            'name' => $name,
            'guard_name' => $guard,
        ]);

        return back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
        ]);

        $name = trim((string) $validated['name']);
        $regex = '/^[A-Za-z][A-Za-z0-9_.-]{2,63}$/';
        if (! preg_match($regex, $name)) {
            return back()->with('error', 'Invalid role name.');
        }

        if ($role->name === $name) {
            return back()->with('success', 'Role updated successfully.');
        }

        $exists = Role::query()
            ->where('guard_name', $role->guard_name)
            ->where('name', $name)
            ->where('id', '!=', $role->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Role name already exists.');
        }

        $role->update(['name' => $name]);

        return back()->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'system-admin') {
            return back()->with('error', 'Cannot delete system-admin role.');
        }

        if (Role::query()->count() <= 1) {
            return back()->with('error', 'Cannot delete the last remaining role.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'Cannot delete a role that is assigned to users.');
        }

        if ($role->permissions()->exists()) {
            return back()->with('error', 'Cannot delete a role that has permissions. Revoke permissions first.');
        }

        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array', 'max:500'],
            'permissions.*' => ['string', 'max:190'],
        ]);

        $names = collect($validated['permissions'] ?? [])->filter()->unique()->values();

        $permissions = $names->isEmpty()
            ? collect()
            : Permission::query()->whereIn('name', $names)->where('guard_name', $role->guard_name)->get();

        $missing = $names->diff($permissions->pluck('name'));
        if ($missing->isNotEmpty()) {
            return back()->with('error', 'Some permissions were not found.');
        }

        $role->syncPermissions($permissions);

        return back()->with('success', 'Role permissions updated successfully.');
    }
}
