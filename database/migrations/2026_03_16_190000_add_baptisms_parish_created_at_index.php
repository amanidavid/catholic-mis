<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('baptisms', function (Blueprint $table) {
            $table->index(['parish_id', 'created_at'], 'idx_baptisms_parish_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('baptisms', function (Blueprint $table) {
            $table->dropIndex('idx_baptisms_parish_created_at');
        });
    }
};
