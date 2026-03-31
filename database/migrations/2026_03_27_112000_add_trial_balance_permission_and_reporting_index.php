<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('finance.trial-balance.view', 'web');

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['system-admin', 'parish-treasurer'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        Schema::table('general_ledgers', function (Blueprint $table) {
            $table->index(['transaction_date', 'ledger_id'], 'idx_general_ledgers_date_ledger');
        });
    }

    public function down(): void
    {
        Schema::table('general_ledgers', function (Blueprint $table) {
            $table->dropIndex('idx_general_ledgers_date_ledger');
        });

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['system-admin', 'parish-treasurer'])
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo('finance.trial-balance.view'));

        Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'finance.trial-balance.view')
            ->delete();
    }
};
