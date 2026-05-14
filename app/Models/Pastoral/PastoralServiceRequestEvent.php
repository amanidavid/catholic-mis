<?php

namespace App\Models\Pastoral;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PastoralServiceRequestEvent extends BaseModel
{
    use Auditable;

    protected $table = 'pastoral_service_request_events';

    protected $fillable = [
        'uuid',
        'parish_id',
        'pastoral_service_request_id',
        'action',
        'old_status',
        'new_status',
        'performed_by_user_id',
        'performed_at',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'performed_at' => 'datetime',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(PastoralServiceRequest::class, 'pastoral_service_request_id');
    }

    public function performedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
