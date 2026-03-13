<?php

namespace App\Models\Structure;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parish extends BaseModel
{
    use Auditable;

    protected $table = 'parishes';

    protected $fillable = [
        'uuid',
        'diocese_id',
        'name',
        'code',
        'patron_saint',
        'established_year',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    public function diocese(): BelongsTo
    {
        return $this->belongsTo(Diocese::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }
}
