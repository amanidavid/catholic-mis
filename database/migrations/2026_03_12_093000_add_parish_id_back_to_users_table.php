<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'parish_id')) {
                $table->unsignedBigInteger('parish_id')->nullable()->after('member_id');
                $table->index('parish_id', 'idx_users_parish_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'parish_id')) {
                $table->foreign('parish_id', 'fk_users_parish_id')
                    ->references('id')
                    ->on('parishes')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'parish_id')) {
                $table->dropForeign('fk_users_parish_id');
                $table->dropIndex('idx_users_parish_id');
                $table->dropColumn('parish_id');
            }
        });
    }
};
