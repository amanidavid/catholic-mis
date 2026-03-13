<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function indexExists(string $table, string $indexName): bool
    {
        $dbName = DB::getDatabaseName();

        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$dbName, $table, $indexName]
        );

        return (bool) $row;
    }

    public function up(): void
    {
        Schema::table('parish_staff_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('parish_staff_assignments', 'institution_id')) {
                $table->foreignId('institution_id')
                    ->nullable()
                    ->after('parish_staff_id')
                    ->constrained('institutions', 'id')
                    ->onDelete('restrict');
            }
        });

        if (Schema::hasColumn('parish_staff_assignments', 'institution_id')
            && Schema::hasColumn('parish_staff_assignments', 'is_active')
            && ! $this->indexExists('parish_staff_assignments', 'idx_staff_assignments_institution_active')) {
            Schema::table('parish_staff_assignments', function (Blueprint $table) {
                $table->index(['institution_id', 'is_active'], 'idx_staff_assignments_institution_active');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('parish_staff_assignments', 'idx_staff_assignments_institution_active')) {
            Schema::table('parish_staff_assignments', function (Blueprint $table) {
                $table->dropIndex('idx_staff_assignments_institution_active');
            });
        }

        Schema::table('parish_staff_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('parish_staff_assignments', 'institution_id')) {
                $table->dropConstrainedForeignId('institution_id');
            }
        });
    }
};
