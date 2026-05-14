<?php

namespace App\Services\Pastoral;

use App\Models\Pastoral\PastoralServiceRequestEvent;
use Illuminate\Http\Request;

class PastoralServiceRequestEventService
{
    public function record(
        Request $request,
        int $parishId,
        int $serviceRequestId,
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $notes = null
    ): PastoralServiceRequestEvent {
        return PastoralServiceRequestEvent::query()->create([
            'parish_id' => $parishId,
            'pastoral_service_request_id' => $serviceRequestId,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'performed_by_user_id' => $request->user()?->id,
            'performed_at' => now(),
            'notes' => $notes,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
