<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
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
            $role->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        Permission::where('guard_name', 'web')
            ->whereIn('name', [
                'outstations.view',
                'outstations.create',
                'outstations.update',
                'outstations.delete',
            ])
            ->delete();
    }
};
