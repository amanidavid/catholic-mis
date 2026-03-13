<?php

namespace App\Policies;

use App\Models\People\FamilyRelationship;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FamilyRelationshipPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('family-relationships.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view family relationships.');
    }

    public function view(User $user, FamilyRelationship $relationship): Response
    {
        return $user->can('family-relationships.view')
            ? Response::allow()
            : Response::deny('You do not have permission to view family relationships.');
    }

    public function create(User $user): Response
    {
        return $user->can('family-relationships.create')
            ? Response::allow()
            : Response::deny('You do not have permission to create family relationships.');
    }

    public function update(User $user, FamilyRelationship $relationship): Response
    {
        return $user->can('family-relationships.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update family relationships.');
    }

    public function delete(User $user, FamilyRelationship $relationship): Response
    {
        return $user->can('family-relationships.delete')
            ? Response::allow()
            : Response::deny('You do not have permission to delete family relationships.');
    }
}
