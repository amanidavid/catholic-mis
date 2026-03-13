<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unique('phone', 'uq_members_phone');
            $table->unique('national_id', 'uq_members_national_id');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('uq_members_phone');
            $table->dropUnique('uq_members_national_id');
        });
    }
};
