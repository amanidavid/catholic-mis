<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasGender = Schema::hasColumn('parish_staff', 'gender');
        if (! $hasGender) {
            Schema::table('parish_staff', function (Blueprint $table) {
                $table->enum('gender', ['male', 'female'])->nullable()->after('national_id');
            });
        }

        $indexes = collect(DB::select("SHOW INDEX FROM `parish_staff`"))->pluck('Key_name')->unique();

        if (! $indexes->contains('idx_parish_staff_parish_gender')) {
            Schema::table('parish_staff', function (Blueprint $table) {
                $table->index(['parish_id', 'gender'], 'idx_parish_staff_parish_gender');
            });
        }

        if (! $indexes->contains('uq_parish_staff_parish_member')) {
            Schema::table('parish_staff', function (Blueprint $table) {
                $table->unique(['parish_id', 'member_id'], 'uq_parish_staff_parish_member');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM `parish_staff`"))->pluck('Key_name')->unique();

        Schema::table('parish_staff', function (Blueprint $table) use ($indexes) {
            if ($indexes->contains('uq_parish_staff_parish_member')) {
                $table->dropUnique('uq_parish_staff_parish_member');
            }

            if ($indexes->contains('idx_parish_staff_parish_gender')) {
                $table->dropIndex('idx_parish_staff_parish_gender');
            }

            if (Schema::hasColumn('parish_staff', 'gender')) {
                $table->dropColumn('gender');
            }
        });
    }
};
