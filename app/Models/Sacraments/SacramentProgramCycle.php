<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SacramentProgramCycle extends BaseModel
{
    use Auditable;

    protected static function booted()
    {
        parent::booted();

        static::saving(function (self $model) {
            $name = trim((string) ($model->name ?? ''));
            $model->name = $name;

            $normalized = preg_replace('/\s+/', ' ', $name);
            $model->name_normalized = strtolower((string) $normalized);
        });
    }

    protected $table = 'sacrament_program_cycles';

    public const PROGRAM_FIRST_COMMUNION = 'first_communion';
    public const PROGRAM_CONFIRMATION = 'confirmation';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'uuid',
        'program',
        'parish_id',
        'name',
        'name_normalized',
        'registration_opens_at',
        'registration_closes_at',
        'late_registration_closes_at',
        'status',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'late_registration_closes_at' => 'datetime',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(SacramentProgramRegistration::class, 'program_cycle_id');
    }

    public function isWithinRegistrationWindow(?\Illuminate\Support\Carbon $now = null): bool
    {
        $now = $now ?: now();

        $opens = $this->registration_opens_at?->copy()?->startOfDay();
        $closes = $this->registration_closes_at?->copy()?->endOfDay();
        $lateCloses = $this->late_registration_closes_at?->copy()?->endOfDay();

        $isOpenWindow = (! $opens || $opens <= $now) && (! $closes || $closes >= $now);
        $isLateWindow = (! $isOpenWindow) && ($lateCloses && $lateCloses >= $now);

        return $isOpenWindow || $isLateWindow;
    }

    public function allowsRegistrationActions(?\Illuminate\Support\Carbon $now = null): bool
    {
        $status = (string) ($this->status ?? '');
        if ($status !== self::STATUS_OPEN) {
            return false;
        }

        return $this->isWithinRegistrationWindow($now);
    }
}
