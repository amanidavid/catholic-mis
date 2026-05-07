<?php

namespace App\Models\Kitume;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParishAssociationLeadership extends BaseModel
{
    use Auditable;

    protected $table = 'parish_association_leaderships';

    protected $fillable = [
        'uuid',
        'parish_association_id',
        'member_id',
        'parish_association_leader_role_id',
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

    public function association(): BelongsTo
    {
        return $this->belongsTo(ParishAssociation::class, 'parish_association_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ParishAssociationLeaderRole::class, 'parish_association_leader_role_id');
    }
}
