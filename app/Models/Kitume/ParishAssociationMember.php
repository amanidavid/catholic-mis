<?php

namespace App\Models\Kitume;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParishAssociationMember extends BaseModel
{
    use Auditable;

    protected $table = 'parish_association_members';

    protected $fillable = [
        'uuid',
        'parish_association_id',
        'member_id',
        'joined_at',
        'end_date',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'joined_at' => 'date',
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
}
