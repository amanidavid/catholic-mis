<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dbName = DB::getDatabaseName();

        $hasNameKey = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'parish_staff_assignment_roles')
            ->where('COLUMN_NAME', 'name_key')
            ->exists();

        if (! $hasNameKey) {
            Schema::table('parish_staff_assignment_roles', function (Blueprint $table) {
                $table->string('name_key')->nullable()->after('name');
            });
        }

        DB::table('parish_staff_assignment_roles')
            ->select(['id', 'name', 'name_key'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $existing = is_string($row->name_key ?? null) ? trim((string) $row->name_key) : '';
                    if ($existing !== '') {
                        continue;
                    }

                    $name = is_string($row->name ?? null) ? (string) $row->name : '';
                    $normalized = preg_replace('/\s+/u', ' ', trim(strip_tags($name)));
                    $normalized = is_string($normalized) ? $normalized : '';
                    $key = mb_strtolower($normalized, 'UTF-8');

                    DB::table('parish_staff_assignment_roles')
                        ->where('id', (int) $row->id)
                        ->update(['name_key' => $key]);
                }
            });

        $hasUnique = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'parish_staff_assignment_roles')
            ->where('INDEX_NAME', 'uq_staff_assignment_roles_parish_name_key')
            ->exists();

        if (! $hasUnique) {
            Schema::table('parish_staff_assignment_roles', function (Blueprint $table) {
                $table->unique(['parish_id', 'name_key'], 'uq_staff_assignment_roles_parish_name_key');
            });
        }
    }

    public function down(): void
    {
        $dbName = DB::getDatabaseName();

        $hasUnique = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'parish_staff_assignment_roles')
            ->where('INDEX_NAME', 'uq_staff_assignment_roles_parish_name_key')
            ->exists();

        if ($hasUnique) {
            Schema::table('parish_staff_assignment_roles', function (Blueprint $table) {
                $table->dropUnique('uq_staff_assignment_roles_parish_name_key');
            });
        }

        $hasNameKey = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'parish_staff_assignment_roles')
            ->where('COLUMN_NAME', 'name_key')
            ->exists();

        if ($hasNameKey) {
            Schema::table('parish_staff_assignment_roles', function (Blueprint $table) {
                $table->dropColumn('name_key');
            });
        }
    }
};
