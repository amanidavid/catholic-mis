<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Structure\Outstation;

trait ResolvesOutstationScope
{
    protected function resolveOutstationIdForParish(int $parishId, ?string $outstationUuid): ?int
    {
        $uuid = is_string($outstationUuid) ? trim($outstationUuid) : '';
        if ($uuid === '' || $parishId <= 0) {
            return null;
        }

        $outstationId = (int) (Outstation::query()
            ->where('parish_id', $parishId)
            ->where('uuid', $uuid)
            ->value('id') ?? 0);

        return $outstationId > 0 ? $outstationId : null;
    }
}
