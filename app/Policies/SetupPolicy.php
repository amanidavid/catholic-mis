<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class SetupPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('settings.manage')
            ? Response::allow()
            : Response::deny('You do not have permission to manage setup.');
    }

    public function view(User $user): Response
    {
        return $user->can('settings.manage')
            ? Response::allow()
            : Response::deny('You do not have permission to manage setup.');
    }

    public function update(User $user): Response
    {
        return $user->can('settings.manage')
            ? Response::allow()
            : Response::deny('You do not have permission to manage setup.');
    }
}
