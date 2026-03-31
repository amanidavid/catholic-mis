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

            'reports.view',
            'reports.dashboard.view',
            'reports.scope.parish',
            'reports.scope.zone',
            'reports.scope.jumuiya',
            'reports.community.view',
            'reports.sacraments.view',

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

            'chart-of-accounts.groups.view',
            'chart-of-accounts.groups.create',
            'chart-of-accounts.groups.update',
            'chart-of-accounts.groups.delete',
            'chart-of-accounts.types.view',
            'chart-of-accounts.types.create',
            'chart-of-accounts.types.update',
            'chart-of-accounts.types.delete',
            'chart-of-accounts.subtypes.view',
            'chart-of-accounts.subtypes.create',
            'chart-of-accounts.subtypes.update',
            'chart-of-accounts.subtypes.delete',
            'chart-of-accounts.ledgers.view',
            'chart-of-accounts.ledgers.create',
            'chart-of-accounts.ledgers.update',
            'chart-of-accounts.ledgers.delete',

            'finance.journals.view',
            'finance.journals.create',
            'finance.journals.post',
            'finance.general-ledger.view',
            'finance.trial-balance.view',
            'finance.petty-cash-funds.view',
            'finance.petty-cash-funds.create',
            'finance.petty-cash-funds.update',
            'finance.petty-cash-vouchers.view',
            'finance.petty-cash-vouchers.create',
            'finance.petty-cash-vouchers.approve',
            'finance.petty-cash-vouchers.post',
            'finance.petty-cash-vouchers.update',
            'finance.petty-cash-vouchers.cancel',
            'finance.petty-cash-replenishments.view',
            'finance.petty-cash-replenishments.create',
            'finance.petty-cash-replenishments.approve',
            'finance.petty-cash-replenishments.post',
            'finance.petty-cash-replenishments.cancel',
            'finance.petty-cash-book.view',
            'finance.banks.view',
            'finance.banks.create',
            'finance.banks.update',
            'finance.banks.delete',
            'finance.bank-accounts.view',
            'finance.bank-accounts.create',
            'finance.bank-accounts.update',
            'finance.bank-accounts.delete',
            'finance.bank-account-transactions.view',
            'finance.bank-account-transactions.create',
            'finance.bank-account-transactions.update',
            'finance.bank-account-transactions.delete',

            'finance.double-entries.view',
            'finance.double-entries.create',
            'finance.double-entries.update',
            'finance.double-entries.delete',

            'baptisms.view',
            'baptisms.parish.view',
            'baptisms.request.create',
            'baptisms.request.edit',
            'baptisms.request.submit',
            'baptisms.approve',
            'baptisms.reject',
            'baptisms.schedule.manage',
            'baptisms.issue',

            'marriages.view',
            'marriages.parish.view',
            'marriages.request.create',
            'marriages.request.edit',
            'marriages.request.submit',
            'marriages.approve',
            'marriages.reject',
            'marriages.schedule.manage',
            'marriages.issue',

            'marriages.cross_parish.search',
            'marriages.cross_parish.create',

            'communions.view',
            'communions.parish.view',
            'communions.register',
            'communions.approve',
            'communions.reject',
            'communions.complete',
            'communions.issue',
            'communions.cycles.manage',

            'confirmations.view',
            'confirmations.parish.view',
            'confirmations.register',
            'confirmations.approve',
            'confirmations.reject',
            'confirmations.complete',
            'confirmations.issue',
            'confirmations.cycles.manage',

            'sacraments.cycle.override',

            'certificates.view',
            'certificates.issue',
            'certificates.verify',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, $guard);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        $permissionModels = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $permissions)
            ->get();

        Role::findByName('system-admin', $guard)->syncPermissions($permissionModels);
    }
}
