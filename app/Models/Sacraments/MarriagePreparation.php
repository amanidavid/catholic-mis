<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarriagePreparation extends BaseModel
{
    use Auditable;

    protected $table = 'marriage_preparation';

    protected $fillable = [
        'marriage_id',
        'attended_class',
        'retreat_completed',
        'counseling_sessions_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'attended_class' => 'boolean',
            'retreat_completed' => 'boolean',
            'counseling_sessions_count' => 'integer',
        ];
    }

    public function marriage(): BelongsTo
    {
        return $this->belongsTo(Marriage::class);
    }
}
