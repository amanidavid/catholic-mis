<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jumuiya_weekly_meetings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('jumuiya_id')->constrained('jumuiyas', 'id')->onDelete('restrict');
            $table->date('meeting_date');
            $table->foreignId('opened_by_user_id')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['jumuiya_id', 'meeting_date'], 'uq_jumuiya_weekly_meetings_jumuiya_date');
            $table->index(['jumuiya_id', 'meeting_date'], 'idx_jumuiya_weekly_meetings_jumuiya_date');
            $table->index('meeting_date', 'idx_jumuiya_weekly_meetings_date');
        });

        Schema::create('jumuiya_weekly_attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('jumuiya_weekly_meeting_id')
                ->constrained('jumuiya_weekly_meetings', 'id')
                ->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('restrict');
            $table->string('status');
            $table->foreignId('marked_by_user_id')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamp('marked_at');
            $table->timestamps();

            $table->unique(['jumuiya_weekly_meeting_id', 'member_id'], 'uq_jumuiya_weekly_attendances_meeting_member');
            $table->index('member_id', 'idx_jumuiya_weekly_attendances_member');
        });

        Schema::create('jumuiya_weekly_attendance_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('jumuiya_weekly_meeting_id')
                ->constrained('jumuiya_weekly_meetings', 'id')
                ->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members', 'id')->onDelete('restrict');
            $table->unsignedBigInteger('jumuiya_weekly_attendance_id')->nullable();
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->foreignId('performed_by_user_id')->constrained('users', 'id')->onDelete('restrict');
            $table->timestamp('performed_at');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('jumuiya_weekly_attendance_id')
                ->references('id')
                ->on('jumuiya_weekly_attendances')
                ->onDelete('set null');

            $table->index(['jumuiya_weekly_meeting_id', 'performed_at'], 'idx_jumuiya_weekly_attendance_audits_meeting_time');
            $table->index('member_id', 'idx_jumuiya_weekly_attendance_audits_member');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jumuiya_weekly_attendance_audits');
        Schema::dropIfExists('jumuiya_weekly_attendances');
        Schema::dropIfExists('jumuiya_weekly_meetings');
    }
};
