<?php

use App\Models\Clergy\Institution;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('institutions')) {
            return;
        }

        Schema::table('institutions', function (Blueprint $table) {
            if (! Schema::hasColumn('institutions', 'name_key')) {
                $table->string('name_key')->nullable()->after('name');
            }
        });

        Institution::query()
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $name = is_string($row->name) ? $row->name : '';
                    $key = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''), 'UTF-8');

                    DB::table('institutions')
                        ->where('id', $row->id)
                        ->update(['name_key' => $key]);
                }
            });

        if (Schema::hasColumn('institutions', 'name_key')) {
            DB::statement("ALTER TABLE institutions MODIFY name_key VARCHAR(255) NOT NULL");

            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::raw('database()'))
                ->where('table_name', 'institutions')
                ->where('index_name', 'idx_institutions_name_key_unique')
                ->exists();

            if (! $exists) {
                Schema::table('institutions', function (Blueprint $table) {
                    $table->unique('name_key', 'idx_institutions_name_key_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('institutions')) {
            return;
        }

        Schema::table('institutions', function (Blueprint $table) {
            if (Schema::hasColumn('institutions', 'name_key')) {
                $table->dropUnique('idx_institutions_name_key_unique');
                $table->dropColumn('name_key');
            }
        });
    }
};
