<?php

namespace App\Policies;

use App\Models\People\Family;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FamilyPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('families.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view families.');
    }

    public function view(User $user, Family $family): Response
    {
        return $user->can('families.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view families.');
    }

    public function create(User $user): Response
    {
        return $user->can('families.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create families.');
    }

    public function update(User $user, Family $family): Response
    {
        return $user->can('families.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update families.');
    }

    public function delete(User $user, Family $family): Response
    {
        return $user->can('families.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete families.');
    }
}
