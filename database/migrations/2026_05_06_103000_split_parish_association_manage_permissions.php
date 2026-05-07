<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $guard = 'web';

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->copyAssignments(
            'parish-associations.members.manage',
            [
                'parish-associations.members.create',
                'parish-associations.members.update',
                'parish-associations.members.delete',
            ],
        );

        $this->copyAssignments(
            'parish-associations.leadership.manage',
            [
                'parish-associations.leadership.create',
                'parish-associations.leadership.update',
                'parish-associations.leadership.delete',
                'parish-associations.leader-roles.create',
                'parish-associations.leader-roles.update',
                'parish-associations.leader-roles.delete',
            ],
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function copyAssignments(string $sourcePermission, array $targetPermissions): void
    {
        $sourceId = Permission::query()
            ->where('guard_name', $this->guard)
            ->where('name', $sourcePermission)
            ->value('id');

        if (! $sourceId) {
            return;
        }

        $targetIds = Permission::query()
            ->where('guard_name', $this->guard)
            ->whereIn('name', $targetPermissions)
            ->pluck('id')
            ->all();

        if ($targetIds === []) {
            return;
        }

        $roleRows = DB::table('role_has_permissions')
            ->where('permission_id', $sourceId)
            ->get(['role_id']);

        foreach ($roleRows as $row) {
            foreach ($targetIds as $targetId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $targetId,
                    'role_id' => $row->role_id,
                ], []);
            }
        }

        $userRows = DB::table('model_has_permissions')
            ->where('permission_id', $sourceId)
            ->where('model_type', 'App\\Models\\User')
            ->get(['model_id', 'model_type']);

        foreach ($userRows as $row) {
            foreach ($targetIds as $targetId) {
                DB::table('model_has_permissions')->updateOrInsert([
                    'permission_id' => $targetId,
                    'model_type' => $row->model_type,
                    'model_id' => $row->model_id,
                ], []);
            }
        }
    }
};
