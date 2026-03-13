<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditLogPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('audit-logs.view')
            ? Response::allow()
            : Response::deny('You are not allowed to view audit logs.');
    }

    public function view(User $user, AuditLog $auditLog): Response
    {
        return $this->viewAny($user);
    }
}
