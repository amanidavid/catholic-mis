<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarriageSponsor extends BaseModel
{
    use Auditable;

    protected $table = 'marriage_sponsors';

    protected $fillable = [
        'marriage_id',
        'role',
        'full_name',
        'phone',
        'address',
        'relationship',
        'notes',
    ];

    public function marriage(): BelongsTo
    {
        return $this->belongsTo(Marriage::class);
    }
}
