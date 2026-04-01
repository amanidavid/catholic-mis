<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends BaseModel
{
    use Auditable;

    protected $table = 'account_types';

    protected $fillable = [
        'uuid',
        'name',
        'account_group_id',
        'created_by',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'account_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subtypes(): HasMany
    {
        return $this->hasMany(AccountSubtype::class, 'account_type_id');
    }
}
