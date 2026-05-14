<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contribution_rules', function (Blueprint $table) {
            if (Schema::hasColumn('contribution_rules', 'applies_to_id')) {
                $table->dropColumn('applies_to_id');
            }
            if (Schema::hasColumn('contribution_rules', 'applies_to_type')) {
                $table->dropColumn('applies_to_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contribution_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('contribution_rules', 'applies_to_type')) {
                $table->string('applies_to_type', 60)->after('contribution_catalog_id');
            }
            if (! Schema::hasColumn('contribution_rules', 'applies_to_id')) {
                $table->unsignedBigInteger('applies_to_id')->nullable()->after('applies_to_type');
            }
        });
    }
};
