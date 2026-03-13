<?php

namespace App\Models\Clergy;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParishClergyAssignment extends BaseModel
{
    use Auditable;

    protected $table = 'parish_clergy_assignments';

    protected $fillable = [
        'uuid',
        'parish_id',
        'clergy_id',
        'role',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function clergy(): BelongsTo
    {
        return $this->belongsTo(Clergy::class);
    }
}
