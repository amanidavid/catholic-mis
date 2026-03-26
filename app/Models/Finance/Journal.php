<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends BaseModel
{
    protected $table = 'journals';

    protected $fillable = [
        'uuid',
        'journal_no',
        'sequence',
        'journal_year',
        'transaction_date',
        'amount',
        'description',
        'is_posted',
        'posted_at',
        'posted_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'transaction_date' => 'date:Y-m-d',
            'amount' => 'decimal:4',
            'is_posted' => 'boolean',
            'posted_at' => 'datetime:Y-m-d\TH:i:s.v\Z',
            'sequence' => 'integer',
            'journal_year' => 'integer',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
