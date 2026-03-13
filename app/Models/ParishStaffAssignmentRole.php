<?php

namespace App\Models;

use App\Models\Structure\Parish;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParishStaffAssignmentRole extends BaseModel
{
    use Auditable;

    protected $table = 'parish_staff_assignment_roles';

    protected $fillable = [
        'uuid',
        'parish_id',
        'name',
        'name_key',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }
}
