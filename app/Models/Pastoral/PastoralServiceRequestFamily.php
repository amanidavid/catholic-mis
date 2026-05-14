<?php

namespace App\Models\Pastoral;

use App\Models\BaseModel;
use App\Models\People\Family;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PastoralServiceRequestFamily extends BaseModel
{
    use Auditable;

    protected $table = 'pastoral_service_request_families';

    protected $fillable = [
        'uuid',
        'pastoral_service_request_id',
        'family_id',
        'family_notes',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(PastoralServiceRequest::class, 'pastoral_service_request_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PastoralServiceRequestItem::class, 'pastoral_service_request_family_id');
    }
}
