<?php

namespace App\Observers;

use Spatie\Permission\Models\Permission;

class PermissionObserver
{
    public function creating(Permission $permission): void
    {
        $this->fillMetadataIfMissing($permission);
    }

    public function updating(Permission $permission): void
    {
        $this->fillMetadataIfMissing($permission);
    }

    private function fillMetadataIfMissing(Permission $permission): void
    {
        $name = (string) ($permission->name ?? '');
        if (trim($name) === '') {
            return;
        }

        [$moduleRaw, $actionRaw] = array_pad(explode('.', $name, 2), 2, null);

        if ($permission->module === null || trim((string) $permission->module) === '') {
            $permission->module = $moduleRaw ? $this->toTitle((string) $moduleRaw) : null;
        }

        if ($permission->display_name === null || trim((string) $permission->display_name) === '') {
            $permission->display_name = $this->permissionDisplayName($permission->module, $actionRaw ? (string) $actionRaw : null);
        }
    }

    private function toTitle(string $value): string
    {
        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);

        return ucwords(strtolower($value));
    }

    private function permissionDisplayName(?string $module, ?string $actionRaw): string
    {
        $actionRaw = $actionRaw ? trim($actionRaw) : '';
        $action = $actionRaw !== '' ? $this->toTitle($actionRaw) : 'Access';
        $moduleTitle = $module !== null && trim($module) !== '' ? trim($module) : 'Module';

        return trim($action.' '.$moduleTitle);
    }
}
