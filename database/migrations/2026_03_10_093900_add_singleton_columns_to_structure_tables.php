<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('dioceses', 'singleton')) {
            Schema::table('dioceses', function (Blueprint $table) {
                $table->unsignedTinyInteger('singleton')->default(1)->after('uuid');
            });
        }

        if (! Schema::hasColumn('parishes', 'singleton')) {
            Schema::table('parishes', function (Blueprint $table) {
                $table->unsignedTinyInteger('singleton')->default(1)->after('uuid');
            });
        }

        Schema::table('dioceses', function (Blueprint $table) {
            $table->unique('singleton', 'dioceses_singleton_unique');
        });

        Schema::table('parishes', function (Blueprint $table) {
            $table->unique('singleton', 'parishes_singleton_unique');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('parishes', 'singleton')) {
            Schema::table('parishes', function (Blueprint $table) {
                $table->dropUnique('parishes_singleton_unique');
                $table->dropColumn('singleton');
            });
        }

        if (Schema::hasColumn('dioceses', 'singleton')) {
            Schema::table('dioceses', function (Blueprint $table) {
                $table->dropUnique('dioceses_singleton_unique');
                $table->dropColumn('singleton');
            });
        }
    }
};
