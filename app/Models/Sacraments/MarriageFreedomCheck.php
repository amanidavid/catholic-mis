<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarriageFreedomCheck extends BaseModel
{
    use Auditable;

    protected $table = 'marriage_freedom_checks';

    protected $fillable = [
        'marriage_id',
        'party',
        'ever_married_before',
        'previous_marriage_type',
        'previous_spouse_deceased',
        'annulment_exists',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'ever_married_before' => 'boolean',
            'previous_spouse_deceased' => 'boolean',
            'annulment_exists' => 'boolean',
        ];
    }

    public function marriage(): BelongsTo
    {
        return $this->belongsTo(Marriage::class);
    }
}
