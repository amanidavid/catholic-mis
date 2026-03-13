<?php

namespace App\Models\Structure;

use App\Models\BaseModel;
use App\Traits\Auditable;

class Diocese extends BaseModel
{
    use Auditable;

    protected $table = 'dioceses';

    protected $fillable = [
        'uuid',
        'name',
        'archbishop_name',
        'established_year',
        'address',
        'phone',
        'email',
        'website',
        'country',
        'is_active',
    ];
}
