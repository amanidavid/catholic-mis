<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends BaseModel
{
    use Auditable;

    protected $table = 'account_groups';

    protected $fillable = [
        'uuid',
        'name',
        'name_normalized',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
            'code' => 'integer',
            'name_normalized' => 'string',
        ];
    }

    public function types(): HasMany
    {
        return $this->hasMany(AccountType::class, 'account_group_id');
    }
}
