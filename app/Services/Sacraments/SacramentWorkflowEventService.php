<?php

namespace App\Services\Sacraments;

use App\Models\Sacraments\SacramentWorkflowEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SacramentWorkflowEventService
{
    public const ENTITY_PROGRAM_REGISTRATION = 'program_registration';
    public const ENTITY_PROGRAM_CYCLE = 'program_cycle';

    public function record(
        Request $request,
        int $parishId,
        string $entityType,
        int $entityId,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        array $meta = []
    ): SacramentWorkflowEvent {
        $userId = (int) ($request->user()?->id ?? 0);

        return DB::transaction(function () use ($request, $parishId, $entityType, $entityId, $action, $fromStatus, $toStatus, $meta, $userId) {
            return SacramentWorkflowEvent::query()->create([
                'uuid' => (string) Str::uuid(),
                'parish_id' => $parishId,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'performed_by_user_id' => $userId ?: null,
                'performed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => empty($meta) ? null : $meta,
            ]);
        });
    }
}
