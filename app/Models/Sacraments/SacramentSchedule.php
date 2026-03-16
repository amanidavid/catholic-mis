<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SacramentSchedule extends BaseModel
{
    use Auditable;

    protected $table = 'sacrament_schedules';

    protected $fillable = [
        'uuid',
        'parish_id',
        'entity_type',
        'entity_id',
        'scheduled_for',
        'location_parish_id',
        'location_text',
        'status',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'scheduled_for' => 'datetime',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function locationParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'location_parish_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
