<?php

namespace App\Models\Structure;

use App\Models\BaseModel;
use App\Traits\Auditable;
use App\Traits\NormalizesNames;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends BaseModel
{
    use Auditable;

    protected static function booted()
    {
        parent::booted();

        static::saving(function (Zone $model) {
            $model->name = NormalizesNames::normalize($model->name);
        });
    }

    protected $table = 'zones';

    protected $fillable = [
        'uuid',
        'parish_id',
        'name',
        'description',
        'established_year',
        'is_active',
    ];

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function jumuiyas(): HasMany
    {
        return $this->hasMany(Jumuiya::class);
    }
}
