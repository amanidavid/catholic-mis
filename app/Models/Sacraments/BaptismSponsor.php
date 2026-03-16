<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaptismSponsor extends BaseModel
{
    use Auditable;

    protected $table = 'baptism_sponsors';

    protected $fillable = [
        'uuid',
        'baptism_id',
        'role',
        'member_id',
        'full_name',
        'parish_name',
        'phone',
        'email',
    ];

    public function baptism(): BelongsTo
    {
        return $this->belongsTo(Baptism::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
