<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SacramentWorkflowEvent extends BaseModel
{
    use Auditable;

    protected $table = 'sacrament_workflow_events';

    protected $fillable = [
        'uuid',
        'parish_id',
        'entity_type',
        'entity_id',
        'action',
        'from_status',
        'to_status',
        'performed_by_user_id',
        'performed_at',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'performed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
