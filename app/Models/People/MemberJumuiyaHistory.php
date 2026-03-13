<?php

namespace App\Models\People;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Structure\Jumuiya;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberJumuiyaHistory extends BaseModel
{
    use Auditable;

    protected $table = 'member_jumuiya_histories';

    protected $fillable = [
        'uuid',
        'member_id',
        'from_jumuiya_id',
        'to_jumuiya_id',
        'effective_date',
        'reason',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'effective_date' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function fromJumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class, 'from_jumuiya_id');
    }

    public function toJumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class, 'to_jumuiya_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
