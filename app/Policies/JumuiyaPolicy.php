<?php

namespace App\Policies;

use App\Models\Structure\Jumuiya;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class JumuiyaPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('jumuiyas.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view Christian Communities.');
    }

    public function view(User $user, Jumuiya $jumuiya): Response
    {
        return $user->can('jumuiyas.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view Christian Communities.');
    }

    public function create(User $user): Response
    {
        return $user->can('jumuiyas.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create Christian Communities.');
    }

    public function update(User $user, Jumuiya $jumuiya): Response
    {
        return $user->can('jumuiyas.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update Christian Communities.');
    }

    public function delete(User $user, Jumuiya $jumuiya): Response
    {
        return $user->can('jumuiyas.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete Christian Communities.');
    }
}
