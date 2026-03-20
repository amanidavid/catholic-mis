<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $res = DB::selectOne(
            'SELECT COUNT(1) as c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );

        return ((int) ($res->c ?? 0)) > 0;
    }

    public function up(): void
    {
        if (Schema::hasTable('sacrament_program_registrations')) {
            Schema::table('sacrament_program_registrations', function (Blueprint $table) {
                if (! $this->indexExists('sacrament_program_registrations', 'idx_program_registrations_program_parish_created_status')) {
                    $table->index(['program', 'parish_id', 'created_at', 'status'], 'idx_program_registrations_program_parish_created_status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sacrament_program_registrations')) {
            Schema::table('sacrament_program_registrations', function (Blueprint $table) {
                if ($this->indexExists('sacrament_program_registrations', 'idx_program_registrations_program_parish_created_status')) {
                    $table->dropIndex('idx_program_registrations_program_parish_created_status');
                }
            });
        }
    }
};
