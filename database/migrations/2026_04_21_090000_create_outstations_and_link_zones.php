<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outstations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('parish_id')->constrained('parishes', 'id')->onDelete('restrict');
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('parish_id', 'idx_outstations_parish_id');
            $table->unique(['parish_id', 'name'], 'uq_outstations_parish_name');
        });

        Schema::table('zones', function (Blueprint $table) {
            $table->foreignId('outstation_id')->nullable()->after('parish_id')->constrained('outstations', 'id')->onDelete('restrict');
            $table->index('outstation_id', 'idx_zones_outstation_id');
        });

        $zones = DB::table('zones')
            ->select(['id', 'parish_id'])
            ->orderBy('id')
            ->get();

        foreach ($zones as $zone) {
            $parishId = (int) ($zone->parish_id ?? 0);
            if ($parishId <= 0) {
                continue;
            }

            $outstationId = DB::table('outstations')
                ->where('parish_id', $parishId)
                ->where('name', 'Default Outstation')
                ->value('id');

            if (! $outstationId) {
                $now = now();
                $outstationId = DB::table('outstations')->insertGetId([
                    'uuid' => (string) str()->uuid(),
                    'parish_id' => $parishId,
                    'name' => 'Default Outstation',
                    'description' => 'Auto-created during outstation migration.',
                    'established_year' => null,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('zones')
                ->where('id', (int) $zone->id)
                ->update(['outstation_id' => $outstationId]);
        }

        Schema::table('zones', function (Blueprint $table) {
            $table->dropUnique('uq_zones_parish_name');
            $table->unique(['outstation_id', 'name'], 'uq_zones_outstation_name');
        });
    }

    public function down(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            $table->dropUnique('uq_zones_outstation_name');
            $table->unique(['parish_id', 'name'], 'uq_zones_parish_name');
            $table->dropForeign(['outstation_id']);
            $table->dropIndex('idx_zones_outstation_id');
            $table->dropColumn('outstation_id');
        });

        Schema::dropIfExists('outstations');
    }
};
