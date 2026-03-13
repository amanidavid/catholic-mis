<?php

namespace App\Models\Clergy;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends BaseModel
{
    use Auditable;

    protected $table = 'institutions';

    protected $fillable = [
        'uuid',
        'name',
        'name_key',
        'type',
        'location',
        'country',
        'contact',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
        ];
    }

    public function clergy(): HasMany
    {
        return $this->hasMany(Clergy::class);
    }
}
