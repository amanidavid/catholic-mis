<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            $table->string('bride_external_zone_name')->nullable()->after('bride_external_home_parish_name');
            $table->string('bride_external_jumuiya_name')->nullable()->after('bride_external_zone_name');
        });
    }

    public function down(): void
    {
        Schema::table('marriages', function (Blueprint $table) {
            $table->dropColumn([
                'bride_external_zone_name',
                'bride_external_jumuiya_name',
            ]);
        });
    }
};
