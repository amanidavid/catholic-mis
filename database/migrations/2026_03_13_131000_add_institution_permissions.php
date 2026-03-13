<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $perms = [
            'institutions.view',
            'institutions.create',
            'institutions.update',
            'institutions.delete',
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
                'institutions.view',
                'institutions.create',
                'institutions.update',
                'institutions.delete',
            ])
            ->delete();
    }
};
