<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Structure\Zone;
use Illuminate\Auth\Access\Response;

class ZonePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('zones.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view zones.');
    }

    public function view(User $user, Zone $zone): Response
    {
        return $user->can('zones.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view zones.');
    }

    public function create(User $user): Response
    {
        return $user->can('zones.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create zones.');
    }

    public function update(User $user, Zone $zone): Response
    {
        return $user->can('zones.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update zones.');
    }

    public function delete(User $user, Zone $zone): Response
    {
        return $user->can('zones.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete zones.');
    }
}
