<?php

namespace App\Models\Certificates;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateSequence extends BaseModel
{
    protected $table = 'certificate_sequences';

    protected $fillable = [
        'parish_id',
        'entity_type',
        'year',
        'next_number',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'year' => 'integer',
            'next_number' => 'integer',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }
}
