<?php

namespace App\Models\Attendance;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JumuiyaWeeklyAttendanceAudit extends BaseModel
{
    protected $table = 'jumuiya_weekly_attendance_audits';

    protected $fillable = [
        'uuid',
        'jumuiya_weekly_meeting_id',
        'member_id',
        'jumuiya_weekly_attendance_id',
        'action',
        'old_status',
        'new_status',
        'performed_by_user_id',
        'performed_at',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'performed_at' => 'datetime',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(JumuiyaWeeklyMeeting::class, 'jumuiya_weekly_meeting_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(JumuiyaWeeklyAttendance::class, 'jumuiya_weekly_attendance_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
