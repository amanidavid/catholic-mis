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
        if (Schema::hasTable('jumuiya_weekly_attendances')) {
            Schema::table('jumuiya_weekly_attendances', function (Blueprint $table) {
                if (! $this->indexExists('jumuiya_weekly_attendances', 'idx_jw_attendances_meeting_status')) {
                    $table->index(['jumuiya_weekly_meeting_id', 'status'], 'idx_jw_attendances_meeting_status');
                }

                if (! $this->indexExists('jumuiya_weekly_attendances', 'idx_jw_attendances_member_meeting')) {
                    $table->index(['member_id', 'jumuiya_weekly_meeting_id'], 'idx_jw_attendances_member_meeting');
                }
            });
        }

        if (Schema::hasTable('jumuiya_weekly_attendance_audits')) {
            Schema::table('jumuiya_weekly_attendance_audits', function (Blueprint $table) {
                if (! $this->indexExists('jumuiya_weekly_attendance_audits', 'idx_jw_attendance_audits_action_time')) {
                    $table->index(['action', 'performed_at'], 'idx_jw_attendance_audits_action_time');
                }

                if (! $this->indexExists('jumuiya_weekly_attendance_audits', 'idx_jw_attendance_audits_user_time')) {
                    $table->index(['performed_by_user_id', 'performed_at'], 'idx_jw_attendance_audits_user_time');
                }
            });
        }

        if (Schema::hasTable('member_jumuiya_histories')) {
            Schema::table('member_jumuiya_histories', function (Blueprint $table) {
                if (! $this->indexExists('member_jumuiya_histories', 'idx_member_jh_member_effective')) {
                    $table->index(['member_id', 'effective_date'], 'idx_member_jh_member_effective');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('jumuiya_weekly_attendances')) {
            Schema::table('jumuiya_weekly_attendances', function (Blueprint $table) {
                if ($this->indexExists('jumuiya_weekly_attendances', 'idx_jw_attendances_meeting_status')) {
                    $table->dropIndex('idx_jw_attendances_meeting_status');
                }

                if ($this->indexExists('jumuiya_weekly_attendances', 'idx_jw_attendances_member_meeting')) {
                    $table->dropIndex('idx_jw_attendances_member_meeting');
                }
            });
        }

        if (Schema::hasTable('jumuiya_weekly_attendance_audits')) {
            Schema::table('jumuiya_weekly_attendance_audits', function (Blueprint $table) {
                if ($this->indexExists('jumuiya_weekly_attendance_audits', 'idx_jw_attendance_audits_action_time')) {
                    $table->dropIndex('idx_jw_attendance_audits_action_time');
                }

                if ($this->indexExists('jumuiya_weekly_attendance_audits', 'idx_jw_attendance_audits_user_time')) {
                    $table->dropIndex('idx_jw_attendance_audits_user_time');
                }
            });
        }

        if (Schema::hasTable('member_jumuiya_histories')) {
            Schema::table('member_jumuiya_histories', function (Blueprint $table) {
                if ($this->indexExists('member_jumuiya_histories', 'idx_member_jh_member_effective')) {
                    $table->dropIndex('idx_member_jh_member_effective');
                }
            });
        }
    }
};
