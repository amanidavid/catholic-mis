<?php

namespace App\Models\Pastoral;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeceasedRegisterEntry extends BaseModel
{
    use Auditable;

    protected $table = 'deceased_register_entries';

    protected $fillable = [
        'uuid',
        'parish_id',
        'member_id',
        'date_of_death',
        'time_of_death',
        'place_of_death',
        'cause_of_death',
        'death_certificate_number',
        'hospital_or_health_facility',
        'funeral_date',
        'burial_date',
        'burial_location_or_cemetery',
        'funeral_mass_location',
        'priest_or_celebrant_name',
        'homily_or_remarks',
        'notes',
        'recorded_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'date_of_death' => 'date',
            'time_of_death' => 'string',
            'funeral_date' => 'date',
            'burial_date' => 'date',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
