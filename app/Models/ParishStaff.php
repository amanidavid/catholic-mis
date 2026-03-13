<?php

namespace App\Models;

use App\Models\People\Member;
use App\Models\Structure\Parish;
use App\Models\Structure\Jumuiya;
use App\Traits\Auditable;
use App\Traits\NormalizesNames;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParishStaff extends BaseModel
{
    use Auditable;

    protected static function booted()
    {
        parent::booted();

        static::saving(function (ParishStaff $model) {
            $model->first_name = NormalizesNames::normalize($model->first_name);
            $model->middle_name = NormalizesNames::normalize($model->middle_name, true);
            $model->last_name = NormalizesNames::normalize($model->last_name);
        });
    }

    protected $table = 'parish_staff';

    protected $fillable = [
        'uuid',
        'parish_id',
        'member_id',
        'jumuiya_id',
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'email',
        'national_id',
        'gender',
        'has_login',
        'user_id',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'has_login' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function jumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ParishStaffAssignment::class);
    }
}
