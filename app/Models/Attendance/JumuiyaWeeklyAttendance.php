<?php

namespace App\Models\Attendance;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JumuiyaWeeklyAttendance extends BaseModel
{
    protected $table = 'jumuiya_weekly_attendances';

    protected $fillable = [
        'uuid',
        'jumuiya_weekly_meeting_id',
        'member_id',
        'status',
        'marked_by_user_id',
        'marked_at',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'marked_at' => 'datetime',
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

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }
}
