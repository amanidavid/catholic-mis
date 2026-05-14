<?php

namespace App\Models\Pastoral;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PastoralServiceRequest extends BaseModel
{
    use Auditable;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'pastoral_service_requests';

    protected $fillable = [
        'uuid',
        'parish_id',
        'jumuiya_id',
        'requested_by_member_id',
        'request_date',
        'preferred_service_date',
        'scheduled_service_date',
        'urgency',
        'notes',
        'cancel_reason',
        'status',
        'submitted_at',
        'in_progress_at',
        'completed_at',
        'cancelled_at',
        'assigned_to_user_id',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'request_date' => 'date',
            'preferred_service_date' => 'date',
            'scheduled_service_date' => 'date',
            'submitted_at' => 'datetime',
            'in_progress_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function jumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class);
    }

    public function requestedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'requested_by_member_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function families(): HasMany
    {
        return $this->hasMany(PastoralServiceRequestFamily::class, 'pastoral_service_request_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PastoralServiceRequestEvent::class, 'pastoral_service_request_id');
    }
}
