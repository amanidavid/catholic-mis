<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $perms = [
            'weekly-attendance.view',
            'weekly-attendance.record',
            'weekly-attendance.edit',
            'weekly-attendance.override-lock',
            'weekly-attendance.reports.view',
            'weekly-attendance.export',
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
                'weekly-attendance.view',
                'weekly-attendance.record',
                'weekly-attendance.edit',
                'weekly-attendance.override-lock',
                'weekly-attendance.reports.view',
                'weekly-attendance.export',
            ])
            ->delete();
    }
};
