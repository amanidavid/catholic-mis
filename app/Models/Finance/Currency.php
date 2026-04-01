<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\Auditable;

class Currency extends BaseModel
{
    use Auditable;

    protected $table = 'currencies';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'symbol',
        'decimals',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'decimals' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
