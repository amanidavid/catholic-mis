<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Permission creation is managed centrally in RolePermissionSeeder.
    }

    public function down(): void
    {
        // Permission removal is managed centrally in RolePermissionSeeder.
    }
};
