<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneralLedger extends BaseModel
{
    use Auditable;

    protected $table = 'general_ledgers';

    protected $fillable = [
        'uuid',
        'journal_id',
        'ledger_id',
        'description',
        'debit_amount',
        'credit_amount',
        'transaction_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'transaction_date' => 'date:Y-m-d',
            'debit_amount' => 'decimal:4',
            'credit_amount' => 'decimal:4',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }
}
