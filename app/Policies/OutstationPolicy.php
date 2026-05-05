<?php

namespace App\Policies;

use App\Models\Structure\Outstation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OutstationPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('outstations.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view outstations.');
    }

    public function view(User $user, Outstation $outstation): Response
    {
        return $user->can('outstations.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view outstations.');
    }

    public function create(User $user): Response
    {
        return $user->can('outstations.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create outstations.');
    }

    public function update(User $user, Outstation $outstation): Response
    {
        return $user->can('outstations.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update outstations.');
    }

    public function delete(User $user, Outstation $outstation): Response
    {
        return $user->can('outstations.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete outstations.');
    }
}
