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

        $hasChangedByEmail = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('COLUMN_NAME', 'changed_by_email')
            ->exists();

        $hasDescriptionKey = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('COLUMN_NAME', 'description_key')
            ->exists();

        if (! $hasChangedByEmail || ! $hasDescriptionKey) {
            Schema::table('audit_logs', function (Blueprint $table) use ($hasChangedByEmail, $hasDescriptionKey) {
                if (! $hasChangedByEmail) {
                    $table->string('changed_by_email', 255)->nullable()->after('changed_by');
                }

                if (! $hasDescriptionKey) {
                    $table->string('description_key', 255)->nullable()->after('description');
                }
            });
        }

        $hasChangedByEmailIdx = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('INDEX_NAME', 'idx_audit_logs_changed_by_email')
            ->exists();

        if (! $hasChangedByEmailIdx) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('changed_by_email', 'idx_audit_logs_changed_by_email');
            });
        }

        $hasDescriptionKeyIdx = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('INDEX_NAME', 'idx_audit_logs_description_key')
            ->exists();

        if (! $hasDescriptionKeyIdx) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('description_key', 'idx_audit_logs_description_key');
            });
        }

        DB::table('audit_logs')
            ->select(['id', 'changed_by', 'changed_by_email', 'description', 'description_key'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                $userIds = [];
                foreach ($rows as $row) {
                    $id = is_int($row->changed_by ?? null) ? $row->changed_by : (ctype_digit((string) ($row->changed_by ?? '')) ? (int) $row->changed_by : null);
                    if ($id) {
                        $userIds[] = $id;
                    }
                }

                $userIds = array_values(array_unique(array_filter($userIds)));

                $emailMap = [];
                if (count($userIds) > 0) {
                    $emailMap = DB::table('users')
                        ->whereIn('id', $userIds)
                        ->pluck('email', 'id')
                        ->all();
                }

                foreach ($rows as $row) {
                    $updates = [];

                    $existingEmail = is_string($row->changed_by_email ?? null) ? trim((string) $row->changed_by_email) : '';
                    if ($existingEmail === '') {
                        $uid = is_int($row->changed_by ?? null) ? $row->changed_by : (ctype_digit((string) ($row->changed_by ?? '')) ? (int) $row->changed_by : null);
                        $email = $uid && array_key_exists($uid, $emailMap) ? (string) $emailMap[$uid] : '';
                        $email = trim($email);
                        $updates['changed_by_email'] = $email !== '' ? mb_strtolower($email, 'UTF-8') : null;
                    }

                    $existingKey = is_string($row->description_key ?? null) ? trim((string) $row->description_key) : '';
                    if ($existingKey === '') {
                        $desc = is_string($row->description ?? null) ? (string) $row->description : '';
                        $desc = preg_replace('/\s+/u', ' ', trim(strip_tags($desc)));
                        $desc = is_string($desc) ? $desc : '';
                        $key = mb_strtolower($desc, 'UTF-8');
                        if (mb_strlen($key, 'UTF-8') > 255) {
                            $key = mb_substr($key, 0, 255, 'UTF-8');
                        }
                        $updates['description_key'] = $key !== '' ? $key : null;
                    }

                    if (count($updates) > 0) {
                        DB::table('audit_logs')->where('id', (int) $row->id)->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        $dbName = DB::getDatabaseName();

        $hasChangedByEmailIdx = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('INDEX_NAME', 'idx_audit_logs_changed_by_email')
            ->exists();

        $hasDescriptionKeyIdx = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('INDEX_NAME', 'idx_audit_logs_description_key')
            ->exists();

        if ($hasChangedByEmailIdx || $hasDescriptionKeyIdx) {
            Schema::table('audit_logs', function (Blueprint $table) use ($hasChangedByEmailIdx, $hasDescriptionKeyIdx) {
                if ($hasChangedByEmailIdx) {
                    $table->dropIndex('idx_audit_logs_changed_by_email');
                }
                if ($hasDescriptionKeyIdx) {
                    $table->dropIndex('idx_audit_logs_description_key');
                }
            });
        }

        $hasChangedByEmail = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('COLUMN_NAME', 'changed_by_email')
            ->exists();

        $hasDescriptionKey = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', $dbName)
            ->where('TABLE_NAME', 'audit_logs')
            ->where('COLUMN_NAME', 'description_key')
            ->exists();

        if ($hasChangedByEmail || $hasDescriptionKey) {
            Schema::table('audit_logs', function (Blueprint $table) use ($hasChangedByEmail, $hasDescriptionKey) {
                if ($hasChangedByEmail) {
                    $table->dropColumn('changed_by_email');
                }
                if ($hasDescriptionKey) {
                    $table->dropColumn('description_key');
                }
            });
        }
    }
};
