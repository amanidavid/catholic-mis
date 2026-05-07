<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $row = DB::selectOne(
            'SELECT COUNT(1) as c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }

    public function up(): void
    {
        if (Schema::hasTable('parish_associations')) {
            Schema::table('parish_associations', function (Blueprint $table) {
                if (! $this->indexExists('parish_associations', 'idx_pa_parish_sort_name_norm')) {
                    $table->index(['parish_id', 'sort_order', 'name_normalized'], 'idx_pa_parish_sort_name_norm');
                }
            });
        }

        if (Schema::hasTable('parish_association_members')) {
            Schema::table('parish_association_members', function (Blueprint $table) {
                if (! $this->indexExists('parish_association_members', 'idx_pam_assoc_active_joined_member')) {
                    $table->index(['parish_association_id', 'is_active', 'joined_at', 'member_id'], 'idx_pam_assoc_active_joined_member');
                }

                if (! $this->indexExists('parish_association_members', 'idx_pam_assoc_member_active_end')) {
                    $table->index(['parish_association_id', 'member_id', 'is_active', 'end_date'], 'idx_pam_assoc_member_active_end');
                }
            });
        }

        if (Schema::hasTable('parish_association_leaderships')) {
            Schema::table('parish_association_leaderships', function (Blueprint $table) {
                if (! $this->indexExists('parish_association_leaderships', 'idx_pal_assoc_active_start_id')) {
                    $table->index(['parish_association_id', 'is_active', 'start_date', 'id'], 'idx_pal_assoc_active_start_id');
                }

                if (! $this->indexExists('parish_association_leaderships', 'idx_pal_assoc_member_active_end')) {
                    $table->index(['parish_association_id', 'member_id', 'is_active', 'end_date'], 'idx_pal_assoc_member_active_end');
                }

                if (! $this->indexExists('parish_association_leaderships', 'idx_pal_role_active_end')) {
                    $table->index(['parish_association_leader_role_id', 'is_active', 'end_date'], 'idx_pal_role_active_end');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('parish_associations')) {
            Schema::table('parish_associations', function (Blueprint $table) {
                if ($this->indexExists('parish_associations', 'idx_pa_parish_sort_name_norm')) {
                    $table->dropIndex('idx_pa_parish_sort_name_norm');
                }
            });
        }

        if (Schema::hasTable('parish_association_members')) {
            Schema::table('parish_association_members', function (Blueprint $table) {
                if ($this->indexExists('parish_association_members', 'idx_pam_assoc_active_joined_member')) {
                    $table->dropIndex('idx_pam_assoc_active_joined_member');
                }

                if ($this->indexExists('parish_association_members', 'idx_pam_assoc_member_active_end')) {
                    $table->dropIndex('idx_pam_assoc_member_active_end');
                }
            });
        }

        if (Schema::hasTable('parish_association_leaderships')) {
            Schema::table('parish_association_leaderships', function (Blueprint $table) {
                if ($this->indexExists('parish_association_leaderships', 'idx_pal_assoc_active_start_id')) {
                    $table->dropIndex('idx_pal_assoc_active_start_id');
                }

                if ($this->indexExists('parish_association_leaderships', 'idx_pal_assoc_member_active_end')) {
                    $table->dropIndex('idx_pal_assoc_member_active_end');
                }

                if ($this->indexExists('parish_association_leaderships', 'idx_pal_role_active_end')) {
                    $table->dropIndex('idx_pal_role_active_end');
                }
            });
        }
    }
};
