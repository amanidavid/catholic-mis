<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends BaseModel
{
    protected $table = 'journal_lines';

    protected $fillable = [
        'uuid',
        'journal_id',
        'ledger_id',
        'description',
        'debit_amount',
        'credit_amount',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
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
