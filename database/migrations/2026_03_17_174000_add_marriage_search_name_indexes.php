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
            if (! Schema::hasColumn('marriages', 'bride_external_full_name_key')) {
                $table->string('bride_external_full_name_key', 191)->nullable()->after('bride_external_full_name');
            }

            $table->index(['origin_jumuiya_id', 'created_at', 'id'], 'idx_marriages_origin_created_id');
            $table->index(['created_at', 'id'], 'idx_marriages_created_id');

            if (Schema::hasColumn('marriages', 'search_key')) {
                $table->index(['origin_jumuiya_id', 'search_key'], 'idx_marriages_origin_search_key');
            }

            $table->index(['origin_jumuiya_id', 'certificate_no_key'], 'idx_marriages_origin_cert_key');
            $table->index(['origin_jumuiya_id', 'bride_external_full_name_key'], 'idx_marriages_origin_ext_bride_name_key');
        });

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE marriages SET bride_external_full_name_key = LOWER(TRIM(bride_external_full_name)) WHERE bride_external_full_name IS NOT NULL AND (bride_external_full_name_key IS NULL OR bride_external_full_name_key = '')");
        }

        if ($driver === 'pgsql') {
            DB::statement("UPDATE marriages SET bride_external_full_name_key = LOWER(TRIM(bride_external_full_name)) WHERE bride_external_full_name IS NOT NULL AND (bride_external_full_name_key IS NULL OR bride_external_full_name_key = '')");
        }

        if ($driver === 'sqlite') {
            DB::statement("UPDATE marriages SET bride_external_full_name_key = LOWER(TRIM(bride_external_full_name)) WHERE bride_external_full_name IS NOT NULL AND (bride_external_full_name_key IS NULL OR bride_external_full_name_key = '')");
        }
    }

    public function down(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            $table->dropIndex('idx_marriages_origin_created_id');
            $table->dropIndex('idx_marriages_created_id');

            if (Schema::hasColumn('marriages', 'search_key')) {
                $table->dropIndex('idx_marriages_origin_search_key');
            }

            $table->dropIndex('idx_marriages_origin_cert_key');
            $table->dropIndex('idx_marriages_origin_ext_bride_name_key');

            if (Schema::hasColumn('marriages', 'bride_external_full_name_key')) {
                $table->dropColumn('bride_external_full_name_key');
            }
        });
    }
};
