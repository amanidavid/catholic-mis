<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parish_staff', function (Blueprint $table) {
            $table->foreignId('jumuiya_id')
                ->nullable()
                ->after('member_id')
                ->constrained('jumuiyas', 'id')
                ->onDelete('set null');

            $table->index('jumuiya_id', 'idx_parish_staff_jumuiya_id');
        });
    }

    public function down(): void
    {
        Schema::table('parish_staff', function (Blueprint $table) {
            $table->dropIndex('idx_parish_staff_jumuiya_id');
            $table->dropConstrainedForeignId('jumuiya_id');
        });
    }
};
