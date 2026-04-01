<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashVoucherLine extends BaseModel
{
    use Auditable;

    protected $table = 'petty_cash_voucher_lines';

    protected $fillable = [
        'uuid',
        'petty_cash_voucher_id',
        'expense_ledger_id',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'amount' => 'decimal:4',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(PettyCashVoucher::class, 'petty_cash_voucher_id');
    }

    public function expenseLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'expense_ledger_id');
    }
}
