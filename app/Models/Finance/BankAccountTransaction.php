<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccountTransaction extends BaseModel
{
    protected $table = 'bank_account_transactions';

    protected $fillable = [
        'uuid',
        'bank_account_id',
        'double_entry_id',
        'debit_ledger_id',
        'credit_ledger_id',
        'transaction_date',
        'transaction_type',
        'direction',
        'amount',
        'reference_no',
        'description',
        'source_type',
        'source_id',
        'journal_id',
        'is_manual_override',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'transaction_date' => 'date:Y-m-d',
            'amount' => 'decimal:4',
            'is_manual_override' => 'boolean',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function doubleEntry(): BelongsTo
    {
        return $this->belongsTo(DoubleEntry::class, 'double_entry_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function debitLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'debit_ledger_id');
    }

    public function creditLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'credit_ledger_id');
    }
}
