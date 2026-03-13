<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogService
{
    /**
     * Get audit logs for a specific model.
     */
    public function getModelAuditLogs(string $modelType, int $modelId, int $perPage = 50): LengthAwarePaginator
    {
        return AuditLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with(['changedBy:id,uuid,email'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get audit logs filtered by various criteria.
     */
    public function getFilteredAuditLogs(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = AuditLog::query()->with(['changedBy:id,uuid,email']);

        if (isset($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
        }

        if (isset($filters['model_id'])) {
            $query->where('model_id', $filters['model_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['changed_by'])) {
            $query->where('changed_by', $filters['changed_by']);
        }

        $from = $filters['from_date'] ?? null;
        $to = $filters['to_date'] ?? null;

        $from = $from instanceof Carbon ? $from : null;
        $to = $to instanceof Carbon ? $to : null;

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        } elseif ($from) {
            $query->where('created_at', '>=', $from);
        } elseif ($to) {
            $query->where('created_at', '<=', $to);
        }

        if (isset($filters['search'])) {
            $search = is_string($filters['search']) ? trim($filters['search']) : '';
            if ($search !== '') {
                $key = mb_strtolower($search, 'UTF-8');
                $query->where(function ($q) use ($key) {
                    $q->where('description_key', 'like', "{$key}%")
                        ->orWhere('changed_by_email', 'like', "{$key}%");
                });
            }
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get audit summary for a model.
     */
    public function getAuditSummary(string $modelType, int $modelId): array
    {
        $logs = AuditLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->get();

        return [
            'total_changes' => $logs->count(),
            'created_at' => $logs->where('action', 'created')->first()?->created_at,
            'last_updated_at' => $logs->where('action', 'updated')->last()?->created_at,
            'created_by' => $logs->where('action', 'created')->first()?->changedBy,
            'last_updated_by' => $logs->where('action', 'updated')->last()?->changedBy,
            'actions' => $logs->groupBy('action')->map->count(),
        ];
    }
}
