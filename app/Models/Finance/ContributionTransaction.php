<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\Parish;
use App\Models\People\Member;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionTransaction extends BaseModel
{
    use Auditable;

    protected $table = 'contribution_transactions';

    protected $fillable = [
        'uuid',
        'parish_id',
        'contribution_payment_request_id',
        'member_id',
        'transaction_type',
        'amount',
        'payment_method',
        'reference_no',
        'paid_at',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'amount' => 'decimal:4',
            'paid_at' => 'datetime:Y-m-d\TH:i:s.v\Z',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'parish_id');
    }

    public function paymentRequest(): BelongsTo
    {
        return $this->belongsTo(ContributionPaymentRequest::class, 'contribution_payment_request_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
