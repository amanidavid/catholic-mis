<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Structure\Parish;
use Illuminate\Contracts\Auth\Authenticatable;

trait ResolvesSingleParishContext
{
    protected function resolveCurrentParishId(?Authenticatable $user = null): int
    {
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId <= 0) {
            $parishId = (int) (Parish::query()->orderBy('id')->value('id') ?? 0);
        }

        return $parishId;
    }
}
