<?php

namespace App\Models\Certificates;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParishSeal extends BaseModel
{
    protected $table = 'parish_seals';

    protected $fillable = [
        'parish_id',
        'image_path',
        'is_active',
        'valid_from',
        'valid_to',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }
}
