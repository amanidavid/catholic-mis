<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\ParishStaff;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Baptism extends BaseModel
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

    protected $table = 'baptisms';

    protected $fillable = [
        'uuid',
        'parish_id',
        'origin_jumuiya_id',
        'member_id',
        'family_id',
        'birth_date',
        'birth_town',
        'residence',
        'baptism_date',
        'baptism_parish_id',
        'certificate_no',
        'certificate_no_key',
        'father_member_id',
        'father_name',
        'mother_member_id',
        'mother_name',
        'sponsor_member_id',
        'sponsor_name',
        'minister_staff_id',
        'minister_name',
        'status',
        'submitted_at',
        'submitted_by_user_id',
        'approved_at',
        'approved_by_user_id',
        'rejected_at',
        'rejected_by_user_id',
        'rejection_reason',
        'completed_at',
        'issued_at',
        'issued_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'birth_date' => 'date',
            'baptism_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (Baptism $model) {
            $cert = is_string($model->certificate_no ?? null) ? trim((string) $model->certificate_no) : '';
            $model->certificate_no = $cert !== '' ? $cert : null;
            $model->certificate_no_key = $cert !== '' ? mb_strtolower($cert, 'UTF-8') : null;
        });
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function originJumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class, 'origin_jumuiya_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(BaptismSponsor::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SacramentAttachment::class, 'entity_id')
            ->where('entity_type', 'baptism');
    }

    public function baptismParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'baptism_parish_id');
    }

    public function fatherMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'father_member_id');
    }

    public function motherMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mother_member_id');
    }

    public function sponsorMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sponsor_member_id');
    }

    public function ministerStaff(): BelongsTo
    {
        return $this->belongsTo(ParishStaff::class, 'minister_staff_id');
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
