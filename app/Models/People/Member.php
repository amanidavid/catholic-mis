<?php

namespace App\Models\People;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Structure\Jumuiya;
use App\Traits\Auditable;
use App\Traits\NormalizesNames;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Member extends BaseModel
{
    use Auditable;

    private static ?bool $hasNameKeyColumns = null;

    private static function hasNameKeyColumns(): bool
    {
        if (self::$hasNameKeyColumns !== null) {
            return self::$hasNameKeyColumns;
        }

        $has = Schema::hasColumn('members', 'first_name_key')
            && Schema::hasColumn('members', 'middle_name_key')
            && Schema::hasColumn('members', 'last_name_key')
            && Schema::hasColumn('members', 'full_name_key');

        return self::$hasNameKeyColumns = $has;
    }

    protected static function booted()
    {
        parent::booted();

        static::saving(function (Member $model) {
            $model->first_name = NormalizesNames::normalize($model->first_name);
            $model->middle_name = NormalizesNames::normalize($model->middle_name, true);
            $model->last_name = NormalizesNames::normalize($model->last_name);

            if (! self::hasNameKeyColumns()) {
                return;
            }

            $first = is_string($model->first_name ?? null) ? trim((string) $model->first_name) : '';
            $middle = is_string($model->middle_name ?? null) ? trim((string) $model->middle_name) : '';
            $last = is_string($model->last_name ?? null) ? trim((string) $model->last_name) : '';

            $model->first_name_key = $first !== '' ? mb_strtolower($first, 'UTF-8') : null;
            $model->middle_name_key = $middle !== '' ? mb_strtolower($middle, 'UTF-8') : null;
            $model->last_name_key = $last !== '' ? mb_strtolower($last, 'UTF-8') : null;

            $full = trim(preg_replace('/\s+/u', ' ', trim($first.' '.$middle.' '.$last)) ?? '');
            $model->full_name_key = $full !== '' ? mb_strtolower($full, 'UTF-8') : null;
        });
    }

    protected $table = 'members';

    protected $fillable = [
        'uuid',
        'family_id',
        'family_relationship_id',
        'jumuiya_id',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'birth_date',
        'phone',
        'email',
        'national_id',
        'marital_status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function familyRelationship(): BelongsTo
    {
        return $this->belongsTo(FamilyRelationship::class);
    }

    public function jumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class);
    }

    public function jumuiyaHistories(): HasMany
    {
        return $this->hasMany(MemberJumuiyaHistory::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function effectiveJumuiyaIdAsOf(CarbonInterface|string $asOfDate): int
    {
        $asOf = $asOfDate instanceof CarbonInterface
            ? $asOfDate->toDateString()
            : (string) $asOfDate;

        $history = $this->jumuiyaHistories()
            ->whereDate('effective_date', '<=', $asOf)
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        return (int) ($history?->to_jumuiya_id ?? $this->jumuiya_id);
    }
}
