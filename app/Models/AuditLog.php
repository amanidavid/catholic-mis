<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends BaseModel
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'uuid',
        'model_type',
        'model_id',
        'action',
        'old_values',
        'new_values',
        'description',
        'description_key',
        'changed_by',
        'changed_by_email',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
