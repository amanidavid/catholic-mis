<?php

namespace App\Models\People;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberSacramentStatus extends BaseModel
{
    public const TYPE_BAPTISM = 'baptism';
    public const TYPE_COMMUNION = 'communion';
    public const TYPE_CONFIRMATION = 'confirmation';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_BAPTISM = 'baptism';
    public const SOURCE_PROGRAM_REGISTRATION = 'program_registration';

    public const TYPES = [
        self::TYPE_BAPTISM,
        self::TYPE_COMMUNION,
        self::TYPE_CONFIRMATION,
    ];

    protected $table = 'member_sacrament_statuses';

    protected $fillable = [
        'uuid',
        'member_id',
        'sacrament_type',
        'is_received',
        'certificate_no',
        'sacrament_date',
        'source_type',
        'source_record_id',
        'source_record_uuid',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'is_received' => 'boolean',
            'sacrament_date' => 'date',
            'source_record_id' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
