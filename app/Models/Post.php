<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'published',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
