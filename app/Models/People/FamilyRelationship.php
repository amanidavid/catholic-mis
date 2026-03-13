<?php

namespace App\Models\People;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyRelationship extends BaseModel
{
    use Auditable;

    protected $table = 'family_relationships';

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'is_active',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
