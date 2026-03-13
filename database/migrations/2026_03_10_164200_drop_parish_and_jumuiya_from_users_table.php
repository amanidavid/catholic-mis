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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'parish_id')) {
                $table->dropIndex('idx_users_parish_id');
                $table->dropColumn('parish_id');
            }

            if (Schema::hasColumn('users', 'jumuiya_id')) {
                $table->dropIndex('idx_users_jumuiya_id');
                $table->dropColumn('jumuiya_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'parish_id')) {
                $table->unsignedBigInteger('parish_id')->nullable()->after('member_id');
                $table->index('parish_id', 'idx_users_parish_id');
            }

            if (! Schema::hasColumn('users', 'jumuiya_id')) {
                $table->unsignedBigInteger('jumuiya_id')->nullable()->after('parish_id');
                $table->index('jumuiya_id', 'idx_users_jumuiya_id');
            }
        });
    }
};
