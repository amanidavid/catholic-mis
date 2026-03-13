<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            'zones.view',
            'zones.create',
            'zones.update',
            'zones.delete',
            'jumuiyas.view',
            'jumuiyas.create',
            'jumuiyas.update',
            'jumuiyas.delete',
            'jumuiya-leaderships.view',
            'jumuiya-leaderships.create',
            'jumuiya-leaderships.update',
            'jumuiya-leaderships.delete',
            'jumuiya-leadership-roles.view',
            'jumuiya-leadership-roles.create',
            'jumuiya-leadership-roles.update',
            'jumuiya-leadership-roles.delete',
            'audit-logs.view',
            'families.view',
            'families.create',
            'families.update',
            'families.delete',
            'members.view',
            'members.create',
            'members.update',
            'members.delete',
            'members.transfer',
            'family-relationships.view',
            'family-relationships.create',
            'family-relationships.update',
            'family-relationships.delete',

            'weekly-attendance.view',
            'weekly-attendance.record',
            'weekly-attendance.edit',
            'weekly-attendance.override-lock',
            'weekly-attendance.reports.view',
            'weekly-attendance.export',

            'parish-staff.view',
            'parish-staff.create',
            'parish-staff.update',
            'parish-staff.delete',
            'parish-staff.assignments.manage',
            'parish-staff.login.manage',

            'institutions.view',
            'institutions.create',
            'institutions.update',
            'institutions.delete',

            'parish-staff-positions.view',
            'parish-staff-positions.create',
            'parish-staff-positions.update',
            'parish-staff-positions.delete',
            'parish_lists.view',
            'users.manage',
            'permissions.manage',
            'workflows.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, $guard);
        }

        $roles = [
            'system-admin',
            'parish-priest',
            'parish-secretary',
            'parish-treasurer',
            'catechist',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, $guard);
        }

        Role::findByName('system-admin', $guard)->syncPermissions($permissions);
    }
}
