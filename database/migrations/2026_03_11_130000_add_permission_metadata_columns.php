<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        Schema::table($permissionsTable, static function (Blueprint $table) {
            if (! Schema::hasColumn($table->getTable(), 'module')) {
                $table->string('module')->nullable()->after('guard_name');
            }
            if (! Schema::hasColumn($table->getTable(), 'display_name')) {
                $table->string('display_name')->nullable()->after('module');
            }
            if (! Schema::hasColumn($table->getTable(), 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            if (! Schema::hasColumn($table->getTable(), 'sort_order')) {
                $table->unsignedInteger('sort_order')->nullable()->after('description');
            }
        });

        if (Schema::hasColumn($permissionsTable, 'module') && ! $this->hasIndex($permissionsTable, 'permissions_module_index')) {
            Schema::table($permissionsTable, static function (Blueprint $table) {
                $table->index(['module'], 'permissions_module_index');
            });
        }

        $this->backfill($permissionsTable);
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (! Schema::hasTable($permissionsTable)) {
            return;
        }

        if ($this->hasIndex($permissionsTable, 'permissions_module_index')) {
            Schema::table($permissionsTable, static function (Blueprint $table) {
                $table->dropIndex('permissions_module_index');
            });
        }

        Schema::table($permissionsTable, static function (Blueprint $table) {
            if (Schema::hasColumn($table->getTable(), 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn($table->getTable(), 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn($table->getTable(), 'display_name')) {
                $table->dropColumn('display_name');
            }
            if (Schema::hasColumn($table->getTable(), 'module')) {
                $table->dropColumn('module');
            }
        });
    }

    private function backfill(string $permissionsTable): void
    {
        if (! Schema::hasColumn($permissionsTable, 'module') || ! Schema::hasColumn($permissionsTable, 'display_name')) {
            return;
        }

        DB::table($permissionsTable)
            ->select(['id', 'name', 'module', 'display_name'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($permissionsTable) {
                foreach ($rows as $row) {
                    $name = is_string($row->name) ? trim($row->name) : '';
                    if ($name === '') {
                        continue;
                    }

                    [$moduleRaw, $actionRaw] = array_pad(explode('.', $name, 2), 2, null);

                    $module = (is_string($row->module) && trim($row->module) !== '')
                        ? trim($row->module)
                        : ($moduleRaw ? $this->toTitle((string) $moduleRaw) : null);

                    $displayName = (is_string($row->display_name) && trim($row->display_name) !== '')
                        ? trim($row->display_name)
                        : $this->permissionDisplayName($module, is_string($actionRaw) ? $actionRaw : null);

                    DB::table($permissionsTable)
                        ->where('id', $row->id)
                        ->update([
                            'module' => $module,
                            'display_name' => $displayName,
                        ]);
                }
            });
    }

    private function toTitle(string $value): string
    {
        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);

        return ucwords(strtolower($value));
    }

    private function permissionDisplayName(?string $module, ?string $actionRaw): string
    {
        $actionRaw = $actionRaw ? trim($actionRaw) : '';
        $action = $actionRaw !== '' ? $this->toTitle($actionRaw) : 'Access';
        $moduleTitle = $module !== null && trim($module) !== '' ? trim($module) : 'Module';

        return trim($action.' '.$moduleTitle);
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $result = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return ! empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }
};
