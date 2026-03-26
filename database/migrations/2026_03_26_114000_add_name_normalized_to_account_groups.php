<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_groups', function (Blueprint $table) {
            $table->string('name_normalized', 60)->nullable()->after('name');
        });

        $rows = DB::table('account_groups')->select(['id', 'name', 'is_active'])->orderBy('id')->get();

        $seen = [];
        foreach ($rows as $row) {
            $raw = is_string($row->name) ? $row->name : '';
            $key = mb_strtolower(preg_replace('/\s+/u', ' ', trim($raw)), 'UTF-8');

            if (!array_key_exists($key, $seen)) {
                $seen[$key] = (int) $row->id;
                DB::table('account_groups')->where('id', $row->id)->update([
                    'name_normalized' => $key,
                ]);
                continue;
            }

            DB::table('account_groups')->where('id', $row->id)->update([
                'is_active' => false,
                'name_normalized' => $key . '-' . $row->id,
            ]);
        }

        Schema::table('account_groups', function (Blueprint $table) {
            $table->unique('name_normalized', 'account_groups_name_normalized_unique');
            $table->index('name_normalized', 'idx_account_groups_name_normalized');
        });
    }

    public function down(): void
    {
        Schema::table('account_groups', function (Blueprint $table) {
            $table->dropUnique('account_groups_name_normalized_unique');
            $table->dropIndex('idx_account_groups_name_normalized');
            $table->dropColumn('name_normalized');
        });
    }
};
