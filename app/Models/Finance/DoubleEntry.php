<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoubleEntry extends BaseModel
{
    use Auditable;

    protected $table = 'double_entries';

    protected $fillable = [
        'uuid',
        'description',
        'transaction_type',
        'ledger_id',
        'debit_ledger_id',
        'credit_ledger_id',
        'journal_id',
        'created_by',
    ];

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    public function debitLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'debit_ledger_id');
    }

    public function creditLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'credit_ledger_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
