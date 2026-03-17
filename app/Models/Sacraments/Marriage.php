<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\ParishStaff;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\Sacraments\SacramentAttachment;
use App\Models\Sacraments\MarriageSponsor;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Marriage extends BaseModel
{
    use Auditable;

    private static ?bool $hasBrideExternalFullNameKeyColumn = null;
    private static ?bool $hasSearchKeyColumn = null;

    private static function hasBrideExternalFullNameKeyColumn(): bool
    {
        if (self::$hasBrideExternalFullNameKeyColumn !== null) {
            return self::$hasBrideExternalFullNameKeyColumn;
        }

        return self::$hasBrideExternalFullNameKeyColumn = Schema::hasColumn('marriages', 'bride_external_full_name_key');
    }

    private static function hasSearchKeyColumn(): bool
    {
        if (self::$hasSearchKeyColumn !== null) {
            return self::$hasSearchKeyColumn;
        }

        return self::$hasSearchKeyColumn = Schema::hasColumn('marriages', 'search_key');
    }

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

    protected $table = 'marriages';

    protected $fillable = [
        'uuid',
        'parish_id',
        'origin_jumuiya_id',
        'groom_member_id',
        'groom_family_id',
        'groom_jumuiya_id',
        'groom_parish_id',
        'bride_member_id',
        'bride_external_full_name',
        'bride_external_full_name_key',
        'bride_external_phone',
        'bride_external_address',
        'bride_external_home_parish_name',
        'bride_family_id',
        'bride_jumuiya_id',
        'bride_parish_id',
        'marriage_date',
        'marriage_time',
        'marriage_parish_id',
        'wedding_type',
        'certificate_no',
        'certificate_no_key',
        'couple_key',
        'search_key',
        'male_witness_member_id',
        'male_witness_name',
        'male_witness_phone',
        'male_witness_address',
        'male_witness_relationship',
        'female_witness_member_id',
        'female_witness_name',
        'female_witness_phone',
        'female_witness_address',
        'female_witness_relationship',
        'minister_staff_id',
        'minister_name',
        'status',
        'submitted_at',
        'submitted_by_user_id',
        'submitted_origin_jumuiya_id',
        'submitted_by_member_id',
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
            'marriage_date' => 'date',
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

        static::saving(function (Marriage $model) {
            $cert = is_string($model->certificate_no ?? null) ? trim((string) $model->certificate_no) : '';
            $model->certificate_no = $cert !== '' ? $cert : null;
            $model->certificate_no_key = $cert !== '' ? mb_strtolower($cert, 'UTF-8') : null;

            $key = is_string($model->couple_key ?? null) ? trim((string) $model->couple_key) : '';
            $model->couple_key = $key !== '' ? mb_strtolower($key, 'UTF-8') : null;

            if (self::hasSearchKeyColumn()) {
                $ck = is_string($model->couple_key ?? null) ? trim((string) $model->couple_key) : '';
                $model->search_key = $ck !== '' ? mb_strtolower($ck, 'UTF-8') : null;
            }

            if (self::hasBrideExternalFullNameKeyColumn()) {
                $n = is_string($model->bride_external_full_name ?? null) ? trim((string) $model->bride_external_full_name) : '';
                $model->bride_external_full_name_key = $n !== '' ? mb_strtolower($n, 'UTF-8') : null;
            }
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

    public function groom(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'groom_member_id');
    }

    public function bride(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'bride_member_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SacramentAttachment::class, 'entity_id')
            ->where('entity_type', 'marriage');
    }

    public function groomFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'groom_family_id');
    }

    public function brideFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'bride_family_id');
    }

    public function groomJumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class, 'groom_jumuiya_id');
    }

    public function brideJumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class, 'bride_jumuiya_id');
    }

    public function groomParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'groom_parish_id');
    }

    public function brideParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'bride_parish_id');
    }

    public function marriageParish(): BelongsTo
    {
        return $this->belongsTo(Parish::class, 'marriage_parish_id');
    }

    public function ministerStaff(): BelongsTo
    {
        return $this->belongsTo(ParishStaff::class, 'minister_staff_id');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function issuedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function parents(): HasMany
    {
        return $this->hasMany(MarriageParent::class);
    }

    public function sponsors(): HasMany
    {
        return $this->hasMany(MarriageSponsor::class);
    }

    public function freedomChecks(): HasMany
    {
        return $this->hasMany(MarriageFreedomCheck::class);
    }

    public function sacramentalStatuses(): HasMany
    {
        return $this->hasMany(MarriageSacramentalStatus::class);
    }

    public function preparation(): HasOne
    {
        return $this->hasOne(MarriagePreparation::class);
    }
}
