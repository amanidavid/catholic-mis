<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SacramentProgramRegistration extends BaseModel
{
    use Auditable;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ISSUED = 'issued';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_COMPLETED,
        self::STATUS_ISSUED,
    ];

    public const TYPE_BAPTISM_CERTIFICATE = 'baptism_certificate';
    public const TYPE_PARISH_LETTER_COMMUNION_STUDY = 'parish_letter_first_communion_study';

    protected $table = 'sacrament_program_registrations';

    protected $fillable = [
        'uuid',
        'program_cycle_id',
        'program',
        'parish_id',
        'origin_jumuiya_id',
        'family_id',
        'member_id',
        'is_transfer',
        'status',
        'submitted_at',
        'submitted_by_user_id',
        'submitted_by_member_id',
        'approved_at',
        'approved_by_user_id',
        'rejected_at',
        'rejected_by_user_id',
        'rejection_reason',
        'completed_at',
        'issued_at',
        'issued_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_transfer' => 'bool',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SacramentProgramCycle::class, 'program_cycle_id');
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function originJumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class, 'origin_jumuiya_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SacramentAttachment::class, 'entity_id')
            ->where('entity_type', 'program_registration');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
