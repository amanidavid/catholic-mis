<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PettyCashVoucher extends BaseModel
{
    use Auditable;

    protected $table = 'petty_cash_vouchers';

    protected $fillable = [
        'uuid',
        'voucher_no',
        'petty_cash_fund_id',
        'transaction_date',
        'payee_name',
        'reference_no',
        'description',
        'amount',
        'status',
        'journal_id',
        'created_by',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'posted_at',
        'posted_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'transaction_date' => 'date:Y-m-d',
            'amount' => 'decimal:4',
            'submitted_at' => 'datetime:Y-m-d H:i:s',
            'approved_at' => 'datetime:Y-m-d H:i:s',
            'rejected_at' => 'datetime:Y-m-d H:i:s',
            'posted_at' => 'datetime:Y-m-d H:i:s',
            'cancelled_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PettyCashVoucherLine::class, 'petty_cash_voucher_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PettyCashVoucherAttachment::class, 'petty_cash_voucher_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'journal_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
