<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    /**
     * Boot the trait and register model events.
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::logAudit($model, 'created', null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $oldValues = [];
            $newValues = [];

            foreach ($model->getChanges() as $key => $value) {
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $value;
            }

            unset($oldValues['updated_at'], $newValues['updated_at']);

            if (! empty($newValues)) {
                static::logAudit($model, 'updated', $oldValues, $newValues);
            }
        });

        static::deleted(function ($model) {
            static::logAudit($model, 'deleted', $model->getAttributes(), null);
        });
    }

    /**
     * Log an audit entry.
     */
    protected static function logAudit($model, string $action, ?array $oldValues, ?array $newValues): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $email = is_string($user->email ?? null) ? trim((string) $user->email) : '';
        $emailKey = $email !== '' ? mb_strtolower($email, 'UTF-8') : null;

        $description = null;
        $descriptionKey = null;
        if (property_exists($model, 'auditDescription')) {
            $description = is_string($model->auditDescription ?? null) ? trim((string) $model->auditDescription) : null;
        }

        if (is_string($description) && $description !== '') {
            $normalized = preg_replace('/\s+/u', ' ', trim(strip_tags($description)));
            $normalized = is_string($normalized) ? $normalized : '';
            $key = mb_strtolower($normalized, 'UTF-8');
            if (mb_strlen($key, 'UTF-8') > 255) {
                $key = mb_substr($key, 0, 255, 'UTF-8');
            }
            $descriptionKey = $key !== '' ? $key : null;
        }

        AuditLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'description_key' => $descriptionKey,
            'changed_by' => $user->id,
            'changed_by_email' => $emailKey,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Manually log a custom audit entry.
     */
    public function logCustomAudit(string $action, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): AuditLog
    {
        $user = Auth::user();

        if (! $user) {
            throw new \RuntimeException('Unable to write audit log without an authenticated user.');
        }

        $email = is_string($user->email ?? null) ? trim((string) $user->email) : '';
        $emailKey = $email !== '' ? mb_strtolower($email, 'UTF-8') : null;

        $descriptionKey = null;
        if (is_string($description)) {
            $normalized = preg_replace('/\s+/u', ' ', trim(strip_tags($description)));
            $normalized = is_string($normalized) ? $normalized : '';
            $key = mb_strtolower($normalized, 'UTF-8');
            if (mb_strlen($key, 'UTF-8') > 255) {
                $key = mb_substr($key, 0, 255, 'UTF-8');
            }
            $descriptionKey = $key !== '' ? $key : null;
        }

        return AuditLog::create([
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'description_key' => $descriptionKey,
            'changed_by' => $user->id,
            'changed_by_email' => $emailKey,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
