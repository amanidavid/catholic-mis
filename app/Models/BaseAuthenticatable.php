<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BaseAuthenticatable extends Authenticatable
{
    protected static function booted()
    {
        static::creating(function ($model) {
            $table = $model->getTable();

            if (! $table || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'uuid')) {
                return;
            }

            if (empty($model->uuid)) {
                $model->uuid = method_exists(Str::class, 'uuid7')
                    ? (string) Str::uuid7()
                    : (string) Str::uuid();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d\TH:i:s.v\Z',
            'updated_at' => 'datetime:Y-m-d\TH:i:s.v\Z',
        ];
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
