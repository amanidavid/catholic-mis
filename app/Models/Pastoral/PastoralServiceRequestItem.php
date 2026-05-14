<?php

namespace App\Models\Pastoral;

use App\Models\BaseModel;
use App\Models\People\Member;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PastoralServiceRequestItem extends BaseModel
{
    use Auditable;

    protected $table = 'pastoral_service_request_items';

    protected $fillable = [
        'uuid',
        'pastoral_service_request_family_id',
        'pastoral_service_category_id',
        'target_member_id',
        'description',
        'requested_for_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'requested_for_date' => 'date',
        ];
    }

    public function requestFamily(): BelongsTo
    {
        return $this->belongsTo(PastoralServiceRequestFamily::class, 'pastoral_service_request_family_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PastoralServiceCategory::class, 'pastoral_service_category_id');
    }

    public function targetMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'target_member_id');
    }
}
