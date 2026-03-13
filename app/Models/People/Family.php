<?php

namespace App\Models\People;

use App\Models\BaseModel;
use App\Models\Structure\Jumuiya;
use App\Traits\Auditable;
use App\Traits\NormalizesNames;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends BaseModel
{
    use Auditable;

    protected static function booted()
    {
        parent::booted();

        static::saving(function (Family $model) {
            $model->family_name = NormalizesNames::normalize($model->family_name);
        });
    }

    protected $table = 'families';

    protected $fillable = [
        'uuid',
        'jumuiya_id',
        'family_name',
        'family_code',
        'house_number',
        'street',
        'head_of_family_member_id',
        'is_active',
    ];

    public function jumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function headOfFamily(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'head_of_family_member_id');
    }
}
