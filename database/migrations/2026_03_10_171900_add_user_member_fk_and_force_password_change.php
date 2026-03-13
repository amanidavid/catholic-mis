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
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('password');
                $table->index('must_change_password', 'idx_users_must_change_password');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'member_id')) {
                $table->foreign('member_id', 'fk_users_member_id')
                    ->references('id')
                    ->on('members')
                    ->onDelete('set null');

                $table->unique('member_id', 'uq_users_member_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'member_id')) {
                $table->dropUnique('uq_users_member_id');
                $table->dropForeign('fk_users_member_id');
            }

            if (Schema::hasColumn('users', 'must_change_password')) {
                $table->dropIndex('idx_users_must_change_password');
                $table->dropColumn('must_change_password');
            }
        });
    }
};
