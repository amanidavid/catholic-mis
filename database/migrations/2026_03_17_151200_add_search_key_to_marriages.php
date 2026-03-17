<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            if (! Schema::hasColumn('marriages', 'search_key')) {
                $table->string('search_key', 191)->nullable()->after('couple_key');
            }

            $table->index(['parish_id', 'search_key'], 'idx_marriages_parish_search_key');
        });

        // Backfill: store a normalized, prefix-search-friendly key.
        // We keep it simple and DB-only: groom UUID + bride UUID/external lowercased name.
        // This avoids joins and is stable even if names change.
        DB::statement("UPDATE marriages SET search_key = couple_key WHERE couple_key IS NOT NULL AND (search_key IS NULL OR search_key = '')");
    }

    public function down(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            $table->dropIndex('idx_marriages_parish_search_key');
            if (Schema::hasColumn('marriages', 'search_key')) {
                $table->dropColumn('search_key');
            }
        });
    }
};
