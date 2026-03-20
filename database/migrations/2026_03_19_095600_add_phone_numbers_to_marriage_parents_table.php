<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marriage_parents', function (Blueprint $table) {
            $table->string('father_phone', 50)->nullable()->after('father_name');
            $table->string('mother_phone', 50)->nullable()->after('mother_name');
        });
    }

    public function down(): void
    {
        Schema::table('marriage_parents', function (Blueprint $table) {
            $table->dropColumn([
                'father_phone',
                'mother_phone',
            ]);
        });
    }
};
