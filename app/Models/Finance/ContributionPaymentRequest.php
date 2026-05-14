<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\Parish;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContributionPaymentRequest extends BaseModel
{
    use Auditable;

    protected $table = 'contribution_payment_requests';

    protected $fillable = [
        'uuid',
        'parish_id',
        'contribution_catalog_id',
        'source_type',
        'source_id',
        'subject_member_id',
        'payer_member_id',
        'family_id',
        'rule_snapshot_name',
        'rule_snapshot_code',
        'rule_snapshot_amount',
        'currency_code',
        'amount_due',
        'amount_paid',
        'balance',
        'status',
        'due_date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'rule_snapshot_amount' => 'decimal:4',
            'amount_due' => 'decimal:4',
            'amount_paid' => 'decimal:4',
            'balance' => 'decimal:4',
            'due_date' => 'date',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'parish_id');
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(ContributionCatalog::class, 'contribution_catalog_id');
    }

    public function subjectMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'subject_member_id');
    }

    public function payerMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'payer_member_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ContributionTransaction::class, 'contribution_payment_request_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
