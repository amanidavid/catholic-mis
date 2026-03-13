<?php

namespace App\Models\Leadership;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JumuiyaLeadership extends BaseModel
{
    use Auditable;

    protected $table = 'jumuiya_leaderships';

    protected $fillable = [
        'uuid',
        'jumuiya_id',
        'member_id',
        'jumuiya_leadership_role_id',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function jumuiya(): BelongsTo
    {
        return $this->belongsTo(Jumuiya::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(JumuiyaLeadershipRole::class, 'jumuiya_leadership_role_id');
    }
}
