<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ledger extends BaseModel
{
    use Auditable;

    protected $table = 'ledgers';

    protected $fillable = [
        'uuid',
        'name',
        'account_code',
        'account_subtype_id',
        'currency_id',
        'opening_balance',
        'opening_balance_type',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'opening_balance' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function subtype(): BelongsTo
    {
        return $this->belongsTo(AccountSubtype::class, 'account_subtype_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
