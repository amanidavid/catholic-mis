<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends BaseModel
{
    protected $table = 'banks';

    protected $fillable = [
        'uuid',
        'name',
        'name_normalized',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'bank_id');
    }
}
