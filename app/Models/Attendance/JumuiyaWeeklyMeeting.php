<?php

namespace App\Models\Attendance;

use App\Models\BaseModel;
use App\Models\Structure\Jumuiya;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JumuiyaWeeklyMeeting extends BaseModel
{
    protected $table = 'jumuiya_weekly_meetings';

    protected $fillable = [
        'uuid',
        'jumuiya_id',
        'meeting_date',
        'opened_by_user_id',
        'closed_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'meeting_date' => 'date',
            'closed_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function jumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(JumuiyaWeeklyAttendance::class, 'jumuiya_weekly_meeting_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(JumuiyaWeeklyAttendanceAudit::class, 'jumuiya_weekly_meeting_id');
    }
}
