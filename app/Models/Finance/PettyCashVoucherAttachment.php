<?php

namespace App\Models\Finance;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashVoucherAttachment extends BaseModel
{
    use Auditable;

    protected $table = 'petty_cash_voucher_attachments';

    protected $fillable = [
        'uuid',
        'petty_cash_voucher_id',
        'original_name',
        'mime_type',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'sha256',
        'uploaded_by_user_id',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(PettyCashVoucher::class, 'petty_cash_voucher_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
