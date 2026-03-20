<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sacrament_program_cycles', function (Blueprint $table) {
            $table->string('name_normalized', 255)->nullable()->after('name');
        });

        DB::table('sacrament_program_cycles')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $name = (string) ($row->name ?? '');
                    $normalized = strtolower(preg_replace('/\s+/', ' ', trim($name)));
                    DB::table('sacrament_program_cycles')
                        ->where('id', (int) $row->id)
                        ->update([
                            'name_normalized' => $normalized,
                        ]);
                }
            });

        DB::statement("ALTER TABLE `sacrament_program_cycles` MODIFY `name_normalized` VARCHAR(255) NOT NULL");

        Schema::table('sacrament_program_cycles', function (Blueprint $table) {
            $table->unique(['program', 'parish_id', 'name_normalized'], 'uq_program_cycles_program_parish_name_normalized');
        });
    }

    public function down(): void
    {
        Schema::table('sacrament_program_cycles', function (Blueprint $table) {
            $table->dropUnique('uq_program_cycles_program_parish_name_normalized');
            $table->dropColumn('name_normalized');
        });
    }
};
