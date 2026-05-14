<?php

namespace App\Models\Pastoral;

use App\Models\BaseModel;
use App\Models\Structure\Parish;
use App\Traits\Auditable;
use App\Traits\NormalizesNames;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PastoralServiceCategory extends BaseModel
{
    use Auditable;

    protected $table = 'pastoral_service_categories';

    protected $fillable = [
        'uuid',
        'parish_id',
        'name',
        'name_normalized',
        'code',
        'description',
        'sort_order',
        'is_active',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saving(function (PastoralServiceCategory $model): void {
            $name = NormalizesNames::normalize($model->name);
            $model->name = $name;
            $model->name_normalized = is_string($name) && $name !== ''
                ? mb_strtolower($name, 'UTF-8')
                : '';

            $code = is_string($model->code ?? null) ? trim((string) $model->code) : '';
            $model->code = $code !== '' ? mb_strtoupper($code, 'UTF-8') : '';

            if (is_string($model->description ?? null)) {
                $description = preg_replace('/\s+/u', ' ', trim((string) $model->description));
                $model->description = is_string($description) && $description !== '' ? $description : null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(PastoralServiceRequestItem::class, 'pastoral_service_category_id');
    }
}
