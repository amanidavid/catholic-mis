<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jumuiya_leadership_roles', function (Blueprint $table) {
            if (! Schema::hasColumn('jumuiya_leadership_roles', 'system_role_name')) {
                $table->string('system_role_name')->nullable()->after('name');
                $table->index('system_role_name', 'idx_jumuiya_leadership_roles_system_role_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jumuiya_leadership_roles', function (Blueprint $table) {
            if (Schema::hasColumn('jumuiya_leadership_roles', 'system_role_name')) {
                $table->dropIndex('idx_jumuiya_leadership_roles_system_role_name');
                $table->dropColumn('system_role_name');
            }
        });
    }
};
