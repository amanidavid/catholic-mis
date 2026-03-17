<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'first_name_key')) {
                $table->string('first_name_key', 191)->nullable()->after('first_name');
            }
            if (! Schema::hasColumn('members', 'middle_name_key')) {
                $table->string('middle_name_key', 191)->nullable()->after('middle_name');
            }
            if (! Schema::hasColumn('members', 'last_name_key')) {
                $table->string('last_name_key', 191)->nullable()->after('last_name');
            }
            if (! Schema::hasColumn('members', 'full_name_key')) {
                $table->string('full_name_key', 191)->nullable()->after('last_name_key');
            }

            $table->index('first_name_key', 'idx_members_first_name_key');
            $table->index('middle_name_key', 'idx_members_middle_name_key');
            $table->index('last_name_key', 'idx_members_last_name_key');
            $table->index('full_name_key', 'idx_members_full_name_key');
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("UPDATE members SET first_name_key = LOWER(TRIM(first_name)) WHERE first_name IS NOT NULL AND (first_name_key IS NULL OR first_name_key = '')");
            DB::statement("UPDATE members SET middle_name_key = LOWER(TRIM(middle_name)) WHERE middle_name IS NOT NULL AND (middle_name_key IS NULL OR middle_name_key = '')");
            DB::statement("UPDATE members SET last_name_key = LOWER(TRIM(last_name)) WHERE last_name IS NOT NULL AND (last_name_key IS NULL OR last_name_key = '')");
            DB::statement("UPDATE members SET full_name_key = LOWER(TRIM(CONCAT_WS(' ', first_name, middle_name, last_name))) WHERE (full_name_key IS NULL OR full_name_key = '')");
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE members SET first_name_key = LOWER(TRIM(first_name)) WHERE first_name IS NOT NULL AND (first_name_key IS NULL OR first_name_key = '')");
            DB::statement("UPDATE members SET middle_name_key = LOWER(TRIM(middle_name)) WHERE middle_name IS NOT NULL AND (middle_name_key IS NULL OR middle_name_key = '')");
            DB::statement("UPDATE members SET last_name_key = LOWER(TRIM(last_name)) WHERE last_name IS NOT NULL AND (last_name_key IS NULL OR last_name_key = '')");
            DB::statement("UPDATE members SET full_name_key = LOWER(TRIM(CONCAT_WS(' ', first_name, middle_name, last_name))) WHERE (full_name_key IS NULL OR full_name_key = '')");
        }

        if ($driver === 'sqlite') {
            DB::statement("UPDATE members SET first_name_key = LOWER(TRIM(first_name)) WHERE first_name IS NOT NULL AND (first_name_key IS NULL OR first_name_key = '')");
            DB::statement("UPDATE members SET middle_name_key = CASE WHEN middle_name IS NULL THEN NULL ELSE LOWER(TRIM(middle_name)) END WHERE (middle_name_key IS NULL OR middle_name_key = '')");
            DB::statement("UPDATE members SET last_name_key = LOWER(TRIM(last_name)) WHERE last_name IS NOT NULL AND (last_name_key IS NULL OR last_name_key = '')");
            DB::statement("UPDATE members SET full_name_key = LOWER(TRIM(COALESCE(first_name, '') || ' ' || COALESCE(middle_name, '') || ' ' || COALESCE(last_name, ''))) WHERE (full_name_key IS NULL OR full_name_key = '')");
        }
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_first_name_key');
            $table->dropIndex('idx_members_middle_name_key');
            $table->dropIndex('idx_members_last_name_key');
            $table->dropIndex('idx_members_full_name_key');

            if (Schema::hasColumn('members', 'full_name_key')) {
                $table->dropColumn('full_name_key');
            }
            if (Schema::hasColumn('members', 'last_name_key')) {
                $table->dropColumn('last_name_key');
            }
            if (Schema::hasColumn('members', 'middle_name_key')) {
                $table->dropColumn('middle_name_key');
            }
            if (Schema::hasColumn('members', 'first_name_key')) {
                $table->dropColumn('first_name_key');
            }
        });
    }
};
