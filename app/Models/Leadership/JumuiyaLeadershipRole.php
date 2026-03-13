<?php

namespace App\Models\Leadership;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JumuiyaLeadershipRole extends BaseModel
{
    use Auditable;

    protected $table = 'jumuiya_leadership_roles';

    protected $fillable = [
        'uuid',
        'name',
        'system_role_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
        ];
    }

    public function leaderships(): HasMany
    {
        return $this->hasMany(JumuiyaLeadership::class, 'jumuiya_leadership_role_id');
    }
}
