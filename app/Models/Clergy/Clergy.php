<?php

namespace App\Models\Clergy;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clergy extends BaseModel
{
    use Auditable;

    protected $table = 'clergy';

    protected $fillable = [
        'uuid',
        'institution_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'ordination_date',
        'phone',
        'email',
        'clergy_status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'date_of_birth' => 'date',
            'ordination_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function parishAssignments(): HasMany
    {
        return $this->hasMany(ParishClergyAssignment::class);
    }
}
