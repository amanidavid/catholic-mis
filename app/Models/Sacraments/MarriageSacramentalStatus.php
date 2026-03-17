<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarriageSacramentalStatus extends BaseModel
{
    use Auditable;

    protected $table = 'marriage_sacramental_status';

    protected $fillable = [
        'marriage_id',
        'party',
        'is_baptized',
        'baptism_parish_name',
        'is_confirmed',
        'confirmation_parish_name',
        'first_holy_communion_done',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_baptized' => 'boolean',
            'is_confirmed' => 'boolean',
            'first_holy_communion_done' => 'boolean',
        ];
    }

    public function marriage(): BelongsTo
    {
        return $this->belongsTo(Marriage::class);
    }
}
