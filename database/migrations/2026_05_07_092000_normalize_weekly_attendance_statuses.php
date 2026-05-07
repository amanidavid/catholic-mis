<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jumuiya_weekly_attendances')) {
            return;
        }

        DB::table('jumuiya_weekly_attendances')
            ->whereIn('status', ['sick', 'travel'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $legacy = ucfirst((string) $row->status);
                    $existingNotes = is_string($row->notes ?? null) ? trim((string) $row->notes) : '';
                    $notes = $existingNotes !== '' ? '['.$legacy.'] '.$existingNotes : 'Legacy status migrated from '.$legacy.'.';

                    DB::table('jumuiya_weekly_attendances')
                        ->where('id', $row->id)
                        ->update([
                            'status' => 'other',
                            'notes' => $notes,
                            'updated_at' => now(),
                        ]);
                }
            });

        if (Schema::hasTable('jumuiya_weekly_attendance_audits')) {
            DB::table('jumuiya_weekly_attendance_audits')
                ->whereIn('new_status', ['sick', 'travel'])
                ->update([
                    'notes' => DB::raw("CASE
                        WHEN notes IS NULL OR TRIM(notes) = '' THEN CONCAT('Legacy status migrated from ', UPPER(LEFT(new_status, 1)), SUBSTRING(new_status, 2), '.')
                        ELSE CONCAT('[', UPPER(LEFT(new_status, 1)), SUBSTRING(new_status, 2), '] ', notes)
                    END"),
                    'new_status' => 'other',
                    'updated_at' => now(),
                ]);

            DB::table('jumuiya_weekly_attendance_audits')
                ->whereIn('old_status', ['sick', 'travel'])
                ->update([
                    'old_status' => 'other',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Data normalization is intentionally not reversed.
    }
};
