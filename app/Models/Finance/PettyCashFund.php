<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyCashFund extends BaseModel
{
    use Auditable;

    protected $table = 'petty_cash_funds';

    protected $fillable = [
        'uuid',
        'name',
        'code',
        'ledger_id',
        'currency_id',
        'custodian_user_id',
        'imprest_amount',
        'min_reorder_amount',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'imprest_amount' => 'decimal:4',
            'min_reorder_amount' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(PettyCashVoucher::class, 'petty_cash_fund_id');
    }
}
