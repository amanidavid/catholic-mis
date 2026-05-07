<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jumuiya_weekly_attendances') || Schema::hasColumn('jumuiya_weekly_attendances', 'notes')) {
            return;
        }

        Schema::table('jumuiya_weekly_attendances', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('marked_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('jumuiya_weekly_attendances') || ! Schema::hasColumn('jumuiya_weekly_attendances', 'notes')) {
            return;
        }

        Schema::table('jumuiya_weekly_attendances', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
