<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('member_sacrament_statuses')) {
            return;
        }

        if ($this->indexExists('member_sacrament_statuses', 'uq_member_sacrament_statuses_type_certificate')) {
            return;
        }

        Schema::table('member_sacrament_statuses', function (Blueprint $table) {
            $table->unique(['sacrament_type', 'certificate_no'], 'uq_member_sacrament_statuses_type_certificate');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('member_sacrament_statuses')) {
            return;
        }

        if (! $this->indexExists('member_sacrament_statuses', 'uq_member_sacrament_statuses_type_certificate')) {
            return;
        }

        Schema::table('member_sacrament_statuses', function (Blueprint $table) {
            $table->dropUnique('uq_member_sacrament_statuses_type_certificate');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
