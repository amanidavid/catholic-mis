<?php

namespace App\Models\Kitume;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Traits\Auditable;
use App\Traits\NormalizesNames;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParishAssociation extends BaseModel
{
    use Auditable;

    protected static function booted()
    {
        parent::booted();

        static::saving(function (ParishAssociation $model) {
            $name = NormalizesNames::normalize($model->name);
            $model->name = $name;
            $model->name_normalized = $name !== null && $name !== ''
                ? mb_strtolower($name, 'UTF-8')
                : '';

            if (is_string($model->code ?? null)) {
                $code = trim((string) $model->code);
                $model->code = $code !== '' ? mb_strtoupper($code, 'UTF-8') : null;
            }

            if (is_string($model->description ?? null)) {
                $description = preg_replace('/\s+/u', ' ', trim((string) $model->description));
                $model->description = is_string($description) && $description !== '' ? $description : null;
            }
        });
    }

    protected $table = 'parish_associations';

    protected $fillable = [
        'uuid',
        'parish_id',
        'name',
        'name_normalized',
        'code',
        'description',
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

    public function memberships(): HasMany
    {
        return $this->hasMany(ParishAssociationMember::class, 'parish_association_id');
    }

    public function leaderships(): HasMany
    {
        return $this->hasMany(ParishAssociationLeadership::class, 'parish_association_id');
    }
}
