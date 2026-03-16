<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SacramentScheduleChange extends BaseModel
{
    use Auditable;

    protected $table = 'sacrament_schedule_changes';

    protected $fillable = [
        'sacrament_schedule_id',
        'old_scheduled_for',
        'new_scheduled_for',
        'changed_by_user_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'old_scheduled_for' => 'datetime',
            'new_scheduled_for' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(SacramentSchedule::class, 'sacrament_schedule_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
