<?php

namespace App\Models\Certificates;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateIssuance extends BaseModel
{
    use Auditable;

    protected $table = 'certificate_issuances';

    protected $fillable = [
        'uuid',
        'parish_id',
        'entity_type',
        'entity_id',
        'certificate_no',
        'certificate_no_key',
        'issued_at',
        'issued_by_user_id',
        'snapshot_json',
        'pdf_path',
        'pdf_sha256',
        'seal_version',
        'collected_at',
        'collected_by_member_id',
        'collected_by_name',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'issued_at' => 'datetime',
            'collected_at' => 'datetime',
            'snapshot_json' => 'array',
            'seal_version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (CertificateIssuance $model) {
            $cert = is_string($model->certificate_no ?? null) ? trim((string) $model->certificate_no) : '';
            $model->certificate_no = $cert;
            $model->certificate_no_key = mb_strtolower($cert, 'UTF-8');
        });
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function collectedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'collected_by_member_id');
    }
}
