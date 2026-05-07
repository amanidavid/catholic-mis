<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'outstations.view',
            'outstations.create',
            'outstations.update',
            'outstations.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::where('name', 'system-admin')->where('guard_name', 'web')->first();
        if ($role) {
            $permissionModels = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $permissions)
                ->get();

            $role->givePermissionTo($permissionModels);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::where('guard_name', 'web')
            ->whereIn('name', [
                'outstations.view',
                'outstations.create',
                'outstations.update',
                'outstations.delete',
            ])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
