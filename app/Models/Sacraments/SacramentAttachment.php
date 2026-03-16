<?php

namespace App\Models\Sacraments;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SacramentAttachment extends BaseModel
{
    use Auditable;

    protected $table = 'sacrament_attachments';

    protected $fillable = [
        'uuid',
        'parish_id',
        'entity_type',
        'entity_id',
        'type',
        'original_name',
        'mime_type',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'sha256',
        'uploaded_by_user_id',
    ];

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
