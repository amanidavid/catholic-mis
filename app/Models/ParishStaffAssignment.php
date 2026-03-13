<?php

namespace App\Models;

use App\Models\Clergy\Institution;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParishStaffAssignment extends BaseModel
{
    use Auditable;

    protected $table = 'parish_staff_assignments';

    protected $fillable = [
        'uuid',
        'parish_staff_id',
        'institution_id',
        'parish_staff_assignment_role_id',
        'assignment_type',
        'title',
        'start_date',
        'end_date',
        'is_active',
        'notes',
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

    public function staff(): BelongsTo
    {
        return $this->belongsTo(ParishStaff::class, 'parish_staff_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ParishStaffAssignmentRole::class, 'parish_staff_assignment_role_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
