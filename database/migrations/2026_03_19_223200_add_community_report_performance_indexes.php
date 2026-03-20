<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $res = DB::selectOne(
            'SELECT COUNT(1) as c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );

        return ((int) ($res->c ?? 0)) > 0;
    }

    public function up(): void
    {
        if (Schema::hasTable('zones')) {
            Schema::table('zones', function (Blueprint $table) {
                if (! $this->indexExists('zones', 'idx_zones_parish_active_name')) {
                    $table->index(['parish_id', 'is_active', 'name'], 'idx_zones_parish_active_name');
                }
            });
        }

        if (Schema::hasTable('jumuiyas')) {
            Schema::table('jumuiyas', function (Blueprint $table) {
                if (! $this->indexExists('jumuiyas', 'idx_jumuiyas_zone_active_name')) {
                    $table->index(['zone_id', 'is_active', 'name'], 'idx_jumuiyas_zone_active_name');
                }
            });
        }

        if (Schema::hasTable('families')) {
            Schema::table('families', function (Blueprint $table) {
                if (! $this->indexExists('families', 'idx_families_jumuiya_active')) {
                    $table->index(['jumuiya_id', 'is_active'], 'idx_families_jumuiya_active');
                }
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (! $this->indexExists('members', 'idx_members_family_active')) {
                    $table->index(['family_id', 'is_active'], 'idx_members_family_active');
                }

                if (! $this->indexExists('members', 'idx_members_jumuiya_active')) {
                    $table->index(['jumuiya_id', 'is_active'], 'idx_members_jumuiya_active');
                }
            });
        }

        if (Schema::hasTable('parish_staff_assignments')) {
            Schema::table('parish_staff_assignments', function (Blueprint $table) {
                if (! $this->indexExists('parish_staff_assignments', 'idx_staff_assignments_staff_active_end')) {
                    $table->index(['parish_staff_id', 'is_active', 'end_date'], 'idx_staff_assignments_staff_active_end');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('zones')) {
            Schema::table('zones', function (Blueprint $table) {
                if ($this->indexExists('zones', 'idx_zones_parish_active_name')) {
                    $table->dropIndex('idx_zones_parish_active_name');
                }
            });
        }

        if (Schema::hasTable('jumuiyas')) {
            Schema::table('jumuiyas', function (Blueprint $table) {
                if ($this->indexExists('jumuiyas', 'idx_jumuiyas_zone_active_name')) {
                    $table->dropIndex('idx_jumuiyas_zone_active_name');
                }
            });
        }

        if (Schema::hasTable('families')) {
            Schema::table('families', function (Blueprint $table) {
                if ($this->indexExists('families', 'idx_families_jumuiya_active')) {
                    $table->dropIndex('idx_families_jumuiya_active');
                }
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if ($this->indexExists('members', 'idx_members_family_active')) {
                    $table->dropIndex('idx_members_family_active');
                }

                if ($this->indexExists('members', 'idx_members_jumuiya_active')) {
                    $table->dropIndex('idx_members_jumuiya_active');
                }
            });
        }

        if (Schema::hasTable('parish_staff_assignments')) {
            Schema::table('parish_staff_assignments', function (Blueprint $table) {
                if ($this->indexExists('parish_staff_assignments', 'idx_staff_assignments_staff_active_end')) {
                    $table->dropIndex('idx_staff_assignments_staff_active_end');
                }
            });
        }
    }
};
