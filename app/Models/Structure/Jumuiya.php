<?php

namespace App\Models\Structure;

use App\Models\BaseModel;
use App\Traits\Auditable;
use App\Traits\NormalizesNames;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\People\Family;
use App\Models\People\Member;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jumuiya extends BaseModel
{
    use Auditable;

    protected static function booted()
    {
        parent::booted();

        static::saving(function (Jumuiya $model) {
            $model->name = NormalizesNames::normalize($model->name);
        });
    }

    protected $table = 'jumuiyas';

    protected $fillable = [
        'uuid',
        'zone_id',
        'name',
        'meeting_day',
        'established_year',
        'is_active',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function leaderships(): HasMany
    {
        return $this->hasMany(JumuiyaLeadership::class);
    }
}
