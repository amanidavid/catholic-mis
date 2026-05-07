<?php

namespace App\Models\Kitume;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Traits\Auditable;
use App\Traits\NormalizesNames;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParishAssociationLeaderRole extends BaseModel
{
    use Auditable;

    protected static function booted()
    {
        parent::booted();

        static::saving(function (ParishAssociationLeaderRole $model) {
            $name = NormalizesNames::normalize($model->name);
            $model->name = $name;
            $model->name_normalized = $name !== null && $name !== ''
                ? mb_strtolower($name, 'UTF-8')
                : '';
        });
    }

    protected $table = 'parish_association_leader_roles';

    protected $fillable = [
        'uuid',
        'parish_id',
        'name',
        'name_normalized',
        'sort_order',
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

    public function leaderships(): HasMany
    {
        return $this->hasMany(ParishAssociationLeadership::class, 'parish_association_leader_role_id');
    }
}
