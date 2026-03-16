<?php

namespace App\Models\Certificates;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateNumberRule extends BaseModel
{
    protected $table = 'certificate_number_rules';

    protected $fillable = [
        'parish_id',
        'entity_type',
        'prefix',
        'sequence_padding',
        'include_year',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'sequence_padding' => 'integer',
            'include_year' => 'boolean',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }
}
