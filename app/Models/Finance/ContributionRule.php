<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionRule extends BaseModel
{
    use Auditable;

    protected $table = 'contribution_rules';

    protected $fillable = [
        'uuid',
        'parish_id',
        'contribution_catalog_id',
        'amount',
        'currency_code',
        'is_required',
        'allow_partial_payment',
        'allow_override',
        'waiver_allowed',
        'effective_from',
        'effective_to',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'amount' => 'decimal:4',
            'is_required' => 'boolean',
            'allow_partial_payment' => 'boolean',
            'allow_override' => 'boolean',
            'waiver_allowed' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
