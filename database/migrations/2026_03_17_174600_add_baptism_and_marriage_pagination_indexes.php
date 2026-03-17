<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baptisms', function (Blueprint $table) {
            $table->index(['origin_jumuiya_id', 'id'], 'idx_baptisms_origin_id');
            $table->index(['origin_jumuiya_id', 'created_at', 'id'], 'idx_baptisms_origin_created_id');
            $table->index(['parish_id', 'created_at', 'id'], 'idx_baptisms_parish_created_id');
        });

        Schema::table('marriages', function (Blueprint $table) {
            if (! Schema::hasColumn('marriages', 'origin_jumuiya_id')) {
                return;
            }

            $table->index(['origin_jumuiya_id', 'id'], 'idx_marriages_origin_id');
        });
    }

    public function down(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            if (Schema::hasColumn('marriages', 'origin_jumuiya_id')) {
                $table->dropIndex('idx_marriages_origin_id');
            }
        });

        Schema::table('baptisms', function (Blueprint $table) {
            $table->dropIndex('idx_baptisms_origin_id');
            $table->dropIndex('idx_baptisms_origin_created_id');
            $table->dropIndex('idx_baptisms_parish_created_id');
        });
    }
};
