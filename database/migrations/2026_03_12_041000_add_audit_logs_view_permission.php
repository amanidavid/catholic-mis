<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Permission::findOrCreate('audit-logs.view', 'web');
    }

    public function down(): void
    {
        Permission::query()
            ->where('name', 'audit-logs.view')
            ->where('guard_name', 'web')
            ->delete();
    }
};
