<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $perms = [
            'parish-staff.view',
            'parish-staff.create',
            'parish-staff.update',
            'parish-staff.delete',
            'parish-staff.assignments.manage',
            'parish-staff.login.manage',
        ];

        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'parish-staff.view',
                'parish-staff.create',
                'parish-staff.update',
                'parish-staff.delete',
                'parish-staff.assignments.manage',
                'parish-staff.login.manage',
            ])
            ->delete();
    }
};
