<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->index('phone', 'idx_members_phone');
            $table->index('email', 'idx_members_email');
            $table->index('national_id', 'idx_members_national_id');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->index('family_code', 'idx_families_family_code');
            $table->index(['jumuiya_id', 'family_code'], 'idx_families_jumuiya_family_code');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_members_phone');
            $table->dropIndex('idx_members_email');
            $table->dropIndex('idx_members_national_id');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropIndex('idx_families_family_code');
            $table->dropIndex('idx_families_jumuiya_family_code');
        });
    }
};
