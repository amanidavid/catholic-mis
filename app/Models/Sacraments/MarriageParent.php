<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarriageParent extends BaseModel
{
    use Auditable;

    protected $table = 'marriage_parents';

    protected $fillable = [
        'marriage_id',
        'party',
        'father_member_id',
        'father_name',
        'father_phone',
        'father_religion',
        'father_is_alive',
        'mother_member_id',
        'mother_name',
        'mother_phone',
        'mother_religion',
        'mother_is_alive',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'father_is_alive' => 'boolean',
            'mother_is_alive' => 'boolean',
        ];
    }

    public function marriage(): BelongsTo
    {
        return $this->belongsTo(Marriage::class);
    }

    public function fatherMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'father_member_id');
    }

    public function motherMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mother_member_id');
    }
}
