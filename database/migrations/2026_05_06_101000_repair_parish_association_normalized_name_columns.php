<?php

use App\Traits\NormalizesNames;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureNormalizedNameColumn(
            'parish_associations',
            'uq_pa_parish_name_norm',
            'idx_pa_parish_active_sort_name'
        );

        $this->ensureNormalizedNameColumn(
            'parish_association_leader_roles',
            'uq_palr_parish_name_norm',
            'idx_palr_parish_active_sort_name'
        );
    }

    public function down(): void
    {
        // This repair migration is intentionally non-destructive.
    }

    private function ensureNormalizedNameColumn(string $table, string $uniqueIndex, string $sortIndex): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (! Schema::hasColumn($table, 'name_normalized')) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('name_normalized', 255)->nullable()->after('name');
            });
        }

        $this->backfillNormalizedNames($table);

        try {
            DB::statement(sprintf(
                'ALTER TABLE `%s` MODIFY `name_normalized` VARCHAR(255) NOT NULL',
                $table
            ));
        } catch (\Throwable) {
            // Leave the column as-is if the platform cannot modify it.
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($uniqueIndex) {
                $blueprint->unique(['parish_id', 'name_normalized'], $uniqueIndex);
            });
        } catch (\Throwable) {
            // Index may already exist.
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($sortIndex) {
                $blueprint->index(['parish_id', 'is_active', 'sort_order', 'name_normalized'], $sortIndex);
            });
        } catch (\Throwable) {
            // Index may already exist.
        }
    }

    private function backfillNormalizedNames(string $table): void
    {
        $seen = [];

        DB::table($table)
            ->select(['id', 'parish_id', 'name'])
            ->orderBy('parish_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, &$seen) {
                foreach ($rows as $row) {
                    $parishId = (int) ($row->parish_id ?? 0);
                    $name = (string) ($row->name ?? '');
                    $normalized = mb_strtolower((string) (NormalizesNames::normalize($name) ?? ''), 'UTF-8');

                    if ($normalized === '') {
                        $normalized = 'record-'.$row->id;
                    }

                    $uniqueNormalized = $normalized;
                    while (isset($seen[$parishId][$uniqueNormalized])) {
                        $uniqueNormalized = $normalized.'-'.$row->id;
                    }

                    $seen[$parishId][$uniqueNormalized] = true;

                    DB::table($table)
                        ->where('id', (int) $row->id)
                        ->update([
                            'name_normalized' => $uniqueNormalized,
                        ]);
                }
            });
    }
};
