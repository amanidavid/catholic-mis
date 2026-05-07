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

        $this->migrateAssignments(
            'parish-associations.members.manage',
            [
                'parish-associations.members.create',
                'parish-associations.members.update',
                'parish-associations.members.delete',
            ],
        );

        $this->migrateAssignments(
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

        Permission::query()
            ->where('guard_name', $this->guard)
            ->whereIn('name', [
                'parish-associations.members.manage',
                'parish-associations.leadership.manage',
            ])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function migrateAssignments(string $legacyPermission, array $replacementPermissions): void
    {
        $legacyId = Permission::query()
            ->where('guard_name', $this->guard)
            ->where('name', $legacyPermission)
            ->value('id');

        if (! $legacyId) {
            return;
        }

        $replacementIds = Permission::query()
            ->where('guard_name', $this->guard)
            ->whereIn('name', $replacementPermissions)
            ->pluck('id')
            ->all();

        if ($replacementIds === []) {
            return;
        }

        $roleAssignments = DB::table('role_has_permissions')
            ->where('permission_id', $legacyId)
            ->get(['role_id']);

        foreach ($roleAssignments as $assignment) {
            foreach ($replacementIds as $replacementId) {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $replacementId,
                    'role_id' => $assignment->role_id,
                ], []);
            }
        }

        $userAssignments = DB::table('model_has_permissions')
            ->where('permission_id', $legacyId)
            ->where('model_type', 'App\\Models\\User')
            ->get(['model_id', 'model_type']);

        foreach ($userAssignments as $assignment) {
            foreach ($replacementIds as $replacementId) {
                DB::table('model_has_permissions')->updateOrInsert([
                    'permission_id' => $replacementId,
                    'model_type' => $assignment->model_type,
                    'model_id' => $assignment->model_id,
                ], []);
            }
        }
    }
};
