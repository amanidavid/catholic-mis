<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            if (Schema::hasColumn('zones', 'code')) {
                if (Schema::hasColumn('zones', 'parish_id')) {
                    $table->dropIndex('idx_zones_parish_code');
                }
                $table->dropColumn('code');
            }
        });

        Schema::table('jumuiyas', function (Blueprint $table) {
            if (Schema::hasColumn('jumuiyas', 'code')) {
                if (Schema::hasColumn('jumuiyas', 'zone_id')) {
                    $table->dropIndex('idx_jumuiyas_zone_code');
                }
                $table->dropColumn('code');
            }

            if (Schema::hasColumn('jumuiyas', 'patron_saint')) {
                $table->dropColumn('patron_saint');
            }

            if (Schema::hasColumn('jumuiyas', 'meeting_location')) {
                $table->dropColumn('meeting_location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            if (! Schema::hasColumn('zones', 'code')) {
                $table->string('code')->nullable()->after('name');
                if (Schema::hasColumn('zones', 'parish_id')) {
                    $table->index(['parish_id', 'code'], 'idx_zones_parish_code');
                }
            }
        });

        Schema::table('jumuiyas', function (Blueprint $table) {
            if (! Schema::hasColumn('jumuiyas', 'code')) {
                $table->string('code')->nullable()->after('name');
                if (Schema::hasColumn('jumuiyas', 'zone_id')) {
                    $table->index(['zone_id', 'code'], 'idx_jumuiyas_zone_code');
                }
            }

            if (! Schema::hasColumn('jumuiyas', 'patron_saint')) {
                $table->string('patron_saint')->nullable()->after('code');
            }

            if (! Schema::hasColumn('jumuiyas', 'meeting_location')) {
                $table->string('meeting_location')->nullable()->after('meeting_day');
            }
        });
    }
};
