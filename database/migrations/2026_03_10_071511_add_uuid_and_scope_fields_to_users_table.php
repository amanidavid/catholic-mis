<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'uuid')) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('users', 'member_id')) {
                $table->unsignedBigInteger('member_id')->nullable()->after('uuid');
                $table->index('member_id', 'idx_users_member_id');
            }

            if (! Schema::hasColumn('users', 'parish_id')) {
                $table->unsignedBigInteger('parish_id')->nullable()->after('member_id');
                $table->index('parish_id', 'idx_users_parish_id');
            }

            if (! Schema::hasColumn('users', 'jumuiya_id')) {
                $table->unsignedBigInteger('jumuiya_id')->nullable()->after('parish_id');
                $table->index('jumuiya_id', 'idx_users_jumuiya_id');
            }

            if (! Schema::hasColumn('users', 'user_category')) {
                $table->string('user_category')->nullable()->after('jumuiya_id');
                $table->index('user_category', 'idx_users_user_category');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('user_category');
                $table->index('is_active', 'idx_users_is_active');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
        });

        if (Schema::hasColumn('users', 'uuid')) {
            $userIds = DB::table('users')
                ->whereNull('uuid')
                ->pluck('id');

            foreach ($userIds as $id) {
                DB::table('users')
                    ->where('id', $id)
                    ->update([
                        'uuid' => method_exists(Str::class, 'uuid7')
                            ? (string) Str::uuid7()
                            : (string) Str::uuid(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'member_id')) {
                $table->dropIndex('idx_users_member_id');
                $table->dropColumn('member_id');
            }

            if (Schema::hasColumn('users', 'parish_id')) {
                $table->dropIndex('idx_users_parish_id');
                $table->dropColumn('parish_id');
            }

            if (Schema::hasColumn('users', 'jumuiya_id')) {
                $table->dropIndex('idx_users_jumuiya_id');
                $table->dropColumn('jumuiya_id');
            }

            if (Schema::hasColumn('users', 'user_category')) {
                $table->dropIndex('idx_users_user_category');
                $table->dropColumn('user_category');
            }

            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropIndex('idx_users_is_active');
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }

            if (Schema::hasColumn('users', 'uuid')) {
                $table->dropUnique(['uuid']);
                $table->dropColumn('uuid');
            }
        });
    }
};
